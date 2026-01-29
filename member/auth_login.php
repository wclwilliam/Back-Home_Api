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
 * JWT（HS256）工具：不使用第三方套件
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
 * 1️⃣ 讀取 JSON body
 */
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    json_out(400, ["error" => "Invalid JSON body"]);
}

$email = isset($body['email']) ? trim((string)$body['email']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';

/**
 * 2️⃣ 基本驗證
 */
if ($email === '' || $password === '') {
    json_out(400, ["error" => "email, password are required"]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(400, ["error" => "invalid email format"]);
}

// 你想更嚴格可以打開（看你需求）
if (strlen($password) < 8) {
    json_out(400, ["error" => "password must be at least 8 characters"]);
}

/**
 * 3️⃣ 檢查 JWT_SECRET 是否有設定（來自 common/config.php）
 */
if (!defined('JWT_SECRET') || trim((string)JWT_SECRET) === '') {
    // 這是伺服器設定錯誤，不是使用者錯
    json_out(500, ["error" => "server_misconfigured"]);
}

try {
    /**
     * 4️⃣ 查會員
     * ⚠️ 依你的 register API：members / member_email / member_password / member_active / member_realname
     */
    $sql = "
        SELECT member_id, member_realname, member_password, member_active
        FROM members
        WHERE member_email = :email
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":email" => $email]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    // 安全：避免探測 email 是否存在（查不到 & 密碼錯一律回同一句）
    if (!$member) {
        json_out(401, ["error" => "invalid credentials"]);
    }

    // 5️⃣ 檢查啟用狀態
    if ((int)$member['member_active'] !== 1) {
        json_out(403, ["error" => "account is inactive"]);
    }

    // 6️⃣ 密碼比對（雜湊）
    if (!password_verify($password, (string)$member['member_password'])) {
        json_out(401, ["error" => "invalid credentials"]);
    }

    /**
     * 7️⃣ 產生 JWT
     * - iat: 簽發時間
     * - exp: 過期時間
     */
    $now = time();
    $payload = [
        'member_id' => (int)$member['member_id'],
        'iat' => $now,
        'exp' => $now + JWT_EXP_SECONDS_MEMBER,
    ];

    $token = jwt_sign_hs256($payload, (string)JWT_SECRET);

    /**
     * 8️⃣ 回傳：token + member 基本資料
     */
    json_out(200, [
        "status" => "success",
        "token" => $token,
        "member" => [
            "member_id" => (int)$member['member_id'],
            "member_name" => (string)$member['member_realname'],
        ],
    ]);
} catch (Throwable $e) {
    // 正式環境建議不要把錯誤訊息吐給前端，這裡保留你 debug 用
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage(),
    ]);
}
