<?php
declare(strict_types=1);

require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 統一輸出 JSON 並結束
 */
function json_out(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * JWT（HS256）工具
 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_sign_hs256(array $payload, string $secret): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $h = base64url_encode(json_encode($header, JSON_UNESCAPED_UNICODE));
    $p = base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
    $sig = hash_hmac('sha256', $h . '.' . $p, $secret, true);
    $s = base64url_encode($sig);
    return $h . '.' . $p . '.' . $s;
}

/**
 * 解析 JWT（不驗證簽章，僅解析 payload）
 */
function jwt_decode_payload(string $jwt): ?array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    $payload = base64_decode(strtr($parts[1], '-_', '+/'));
    return json_decode($payload, true);
}

/**
 * 用 code 換 LINE access_token 和 id_token
 */
function exchange_line_token(string $code): ?array
{
    $url = 'https://api.line.me/oauth2/v2.1/token';
    $postData = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => LINE_CALLBACK_URL,
        'client_id' => LINE_CHANNEL_ID,
        'client_secret' => LINE_CHANNEL_SECRET,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_CAINFO, BREVO_CA_BUNDLE);  // 使用憑證

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    return json_decode($response, true);
}

/**
 * 取得 LINE 用戶資料（從 id_token 解析）
 */
function get_line_user_profile(string $idToken): ?array
{
    // LINE id_token 是標準 JWT，payload 包含 sub(user_id), name, picture, email
    $payload = jwt_decode_payload($idToken);
    if (!$payload || !isset($payload['sub'])) {
        return null;
    }

    return [
        'user_id' => $payload['sub'],  // LINE User ID（唯一識別）
        'name' => $payload['name'] ?? '',
        'picture' => $payload['picture'] ?? '',
        'email' => $payload['email'] ?? '',
    ];
}

// ===== 主流程 =====

try {
    // 0️⃣ 檢查 LINE 設定
    $channelId = defined('LINE_CHANNEL_ID') ? trim((string)LINE_CHANNEL_ID) : '';
    $channelSecret = defined('LINE_CHANNEL_SECRET') ? trim((string)LINE_CHANNEL_SECRET) : '';
    
    if ($channelId === '' || $channelSecret === '' || 
        $channelId === 'YOUR_LINE_CHANNEL_ID' || 
        $channelId === '請到 LINE Developers Console 申請' ||
        $channelSecret === 'YOUR_LINE_CHANNEL_SECRET' ||
        $channelSecret === '請到 LINE Developers Console 申請') {
        json_out(500, [
            "error" => "LINE Login not configured",
            "message" => "Please set LINE_CHANNEL_ID and LINE_CHANNEL_SECRET in config.php"
        ]);
    }

    // 1️⃣ 讀取 JSON body
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        json_out(400, ["error" => "Invalid JSON body"]);
    }

    $code = isset($body['code']) ? trim((string)$body['code']) : '';
    $state = isset($body['state']) ? trim((string)$body['state']) : '';
    $clientState = isset($body['client_state']) ? trim((string)$body['client_state']) : '';
    $clientNonce = isset($body['client_nonce']) ? trim((string)$body['client_nonce']) : '';

    // 2️⃣ 基本驗證
    if ($code === '' || $state === '') {
        json_out(400, ["error" => "code and state are required"]);
    }

    // 3️⃣ 驗證 state（前端必須傳回當初儲存的 state）
    if ($clientState !== '' && $state !== $clientState) {
        json_out(400, ["error" => "invalid state (CSRF detected)"]);
    }

    // 4️⃣ 用 code 換 token
    $tokenData = exchange_line_token($code);
    if (!$tokenData || !isset($tokenData['id_token'])) {
        json_out(400, ["error" => "failed to exchange LINE token"]);
    }

    // 5️⃣ 解析 LINE 用戶資料
    $lineUser = get_line_user_profile($tokenData['id_token']);
    if (!$lineUser || empty($lineUser['user_id'])) {
        json_out(400, ["error" => "failed to get LINE user profile"]);
    }

    // 6️⃣ 驗證 nonce（可選，增強安全性）
    $payload = jwt_decode_payload($tokenData['id_token']);
    if ($clientNonce !== '' && isset($payload['nonce']) && $payload['nonce'] !== $clientNonce) {
        json_out(400, ["error" => "invalid nonce (replay attack detected)"]);
    }

    $lineUserId = $lineUser['user_id'];
    $lineName = $lineUser['name'];
    $lineEmail = $lineUser['email'];

    // 7️⃣ 決定用於查找/創建會員的 email
    // 如果 LINE 沒提供 email，用 LINE user_id 組成臨時 email
    if (empty($lineEmail)) {
        $lineEmail = 'line_' . $lineUserId . '@backhome.temporary';
    }

    // 8️⃣ 檢查會員是否已存在（用 email）
    $checkSql = "
        SELECT MEMBER_ID, MEMBER_REALNAME, MEMBER_EMAIL, MEMBER_ACTIVE
        FROM MEMBERS
        WHERE MEMBER_EMAIL = :email
        LIMIT 1
    ";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':email' => $lineEmail]);
    $member = $checkStmt->fetch(PDO::FETCH_ASSOC);

    $isNewMember = false;

    // 9️⃣ 如果不存在，自動註冊新會員
    if (!$member) {
        // 生成隨機密碼（第三方登入不會使用，但保持欄位完整性）
        $randomPassword = bin2hex(random_bytes(32));
        $hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);

        $insertSql = "
            INSERT INTO MEMBERS (
                MEMBER_REALNAME, MEMBER_EMAIL, MEMBER_PASSWORD,
                MEMBER_ACTIVE, EMAIL_VERIFIED_AT
            ) VALUES (
                :name, :email, :password, 1, NOW()
            )
        ";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            ':name' => $lineName ?: 'LINE 用戶',
            ':email' => $lineEmail,
            ':password' => $hashedPassword,
        ]);

        $memberId = (int)$pdo->lastInsertId();
        $isNewMember = true;

        // 重新查詢會員資料
        $checkStmt->execute([':email' => $lineEmail]);
        $member = $checkStmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔟 檢查會員是否啟用
    if ((int)$member['MEMBER_ACTIVE'] !== 1) {
        json_out(403, ["error" => "account is inactive"]);
    }

    // 🔟 產生 JWT
    $now = time();
    $payload = [
        'member_id' => (int)$member['MEMBER_ID'],
        'iat' => $now,
        'exp' => $now + JWT_EXP_SECONDS_MEMBER,
    ];

    $token = jwt_sign_hs256($payload, (string)JWT_SECRET);

    // ✅ 回傳成功
    json_out(200, [
        "status" => "success",
        "token" => $token,
        "member" => [
            "member_id" => (int)$member['MEMBER_ID'],
            "member_name" => (string)$member['MEMBER_REALNAME'],
            "member_email" => (string)$member['MEMBER_EMAIL'],
        ],
        "is_new_member" => $isNewMember,
        "line_user_id" => $lineUserId,
    ]);

} catch (Throwable $e) {
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage(),
    ]);
}
