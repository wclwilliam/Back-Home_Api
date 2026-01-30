<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_out(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

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

// 讀取 JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    json_out(400, ["error" => "Invalid JSON body"]);
}

$googleToken = isset($body['credential']) ? trim((string)$body['credential']) : '';

if ($googleToken === '') {
    json_out(400, ["error" => "credential is required"]);
}

// 檢查 JWT_SECRET
if (!defined('JWT_SECRET') || trim((string)JWT_SECRET) === '') {
    json_out(500, ["error" => "server_misconfigured"]);
}

try {
    // 驗證 Google JWT token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($googleToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    if (defined('BREVO_CA_BUNDLE') && BREVO_CA_BUNDLE !== '') {
        curl_setopt($ch, CURLOPT_CAINFO, BREVO_CA_BUNDLE);
    }

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        json_out(401, ["error" => "Invalid Google token"]);
    }

    $googleUser = json_decode($response, true);

    if (!isset($googleUser['email']) || !isset($googleUser['sub'])) {
        json_out(401, ["error" => "Invalid Google token response"]);
    }

    $googleId = $googleUser['sub'];
    $email = $googleUser['email'];
    $name = $googleUser['name'] ?? '';

    // 開始交易
    $pdo->beginTransaction();

    // 用 email 查詢會員
    $checkSql = "SELECT MEMBER_ID, MEMBER_REALNAME, MEMBER_ACTIVE FROM MEMBERS WHERE MEMBER_EMAIL = :email LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':email' => $email]);
    $existingMember = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingMember) {
        // Email 已存在，檢查是否啟用
        if ((int)$existingMember['MEMBER_ACTIVE'] !== 1) {
            $pdo->rollBack();
            json_out(403, ["error" => "account is inactive"]);
        }

        $memberId = (int)$existingMember['MEMBER_ID'];
        $memberName = $existingMember['MEMBER_REALNAME'];
        
        // 更新 email_verified_at（如果尚未驗證）
        $updateSql = "UPDATE MEMBERS SET EMAIL_VERIFIED_AT = COALESCE(EMAIL_VERIFIED_AT, NOW()) WHERE MEMBER_ID = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([':id' => $memberId]);
    } else {
        // 建立新會員
        $insertSql = "
            INSERT INTO MEMBERS (MEMBER_EMAIL, MEMBER_REALNAME, MEMBER_PASSWORD, MEMBER_ACTIVE, EMAIL_VERIFIED_AT)
            VALUES (:email, :name, :password, 1, NOW())
        ";
        
        // Google 登入不需要密碼，設定隨機密碼
        $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            ':email' => $email,
            ':name' => $name,
            ':password' => $randomPassword
        ]);
        
        $memberId = (int)$pdo->lastInsertId();
        $memberName = $name;
    }

    $pdo->commit();

    // 產生 JWT
    $now = time();
    $payload = [
        'member_id' => $memberId,
        'iat' => $now,
        'exp' => $now + JWT_EXP_SECONDS_MEMBER,
    ];

    $token = jwt_sign_hs256($payload, (string)JWT_SECRET);

    // 回傳
    json_out(200, [
        "status" => "success",
        "token" => $token,
        "member" => [
            "member_id" => $memberId,
            "member_name" => $memberName,
        ],
    ]);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage(),
    ]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage(),
    ]);
}
