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

function base64url_decode(string $data): string
{
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    $decoded = base64_decode(strtr($data, '-_', '+/'));
    return $decoded === false ? '' : $decoded;
}

/**
 * 驗證 JWT（HS256）
 * 回傳 payload array 或 null
 */
function jwt_verify_hs256(string $token, string $secret): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$h, $p, $s] = $parts;

    $expectedSig = base64url_encode(hash_hmac('sha256', $h . '.' . $p, $secret, true));
    if (!hash_equals($expectedSig, $s)) return null;

    $headerJson = base64url_decode($h);
    $payloadJson = base64url_decode($p);

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);

    if (!is_array($header) || !is_array($payload)) return null;

    // exp 檢查
    if (!isset($payload['exp']) || time() > (int)$payload['exp']) return null;

    return ['header' => $header, 'payload' => $payload];
}

/**
 * 1️⃣ 讀取 JSON body
 */
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    json_out(400, ["error" => "Invalid JSON body"]);
}

$token = isset($body['token']) ? trim((string)$body['token']) : '';
$newPassword = isset($body['new_password']) ? (string)$body['new_password'] : '';

/**
 * 2️⃣ 基本驗證
 */
if ($token === '' || $newPassword === '') {
    json_out(400, ["error" => "token, new_password are required"]);
}

if (strlen($newPassword) < 8) {
    json_out(400, ["error" => "password must be at least 8 characters"]);
}

// bcrypt 實務限制（避免超長）
if (strlen($newPassword) > 72) {
    json_out(400, ["error" => "password too long"]);
}

/**
 * 3️⃣ 檢查 JWT_SECRET 是否有設定（來自 common/config.php）
 */
if (!defined('JWT_SECRET') || trim((string)JWT_SECRET) === '') {
    json_out(500, ["error" => "server_misconfigured"]);
}

try {
    /**
     * 4️⃣ 驗證 token
     */
    $verified = jwt_verify_hs256($token, (string)JWT_SECRET);
    if (!$verified) {
        json_out(401, ["error" => "invalid_or_expired_token"]);
    }

    $header = $verified['header'];
    $payload = $verified['payload'];

    // typ 檢查：必須是 RESET
    if (($header['typ'] ?? '') !== 'RESET') {
        json_out(401, ["error" => "invalid_or_expired_token"]);
    }

    // 用途檢查：reset_password
    if (($payload['purpose'] ?? '') !== 'reset_password') {
        json_out(401, ["error" => "invalid_or_expired_token"]);
    }

    $memberId = isset($payload['sub']) ? (int)$payload['sub'] : 0;
    $emailInToken = isset($payload['email']) ? (string)$payload['email'] : '';

    if ($memberId <= 0) {
        json_out(401, ["error" => "invalid_or_expired_token"]);
    }

    /**
     * 5️⃣ 查會員
     */
    $sql = "
        SELECT MEMBER_ID, MEMBER_EMAIL, MEMBER_ACTIVE
        FROM MEMBERS
        WHERE MEMBER_ID = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $memberId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        json_out(404, ["error" => "member_not_found"]);
    }

    if ((int)$member['MEMBER_ACTIVE'] !== 1) {
        json_out(403, ["error" => "account is inactive"]);
    }

    // 可選：核對 token email（更嚴謹）
    if ($emailInToken !== '' && strcasecmp($emailInToken, (string)$member['MEMBER_EMAIL']) !== 0) {
        json_out(401, ["error" => "invalid_or_expired_token"]);
    }

    /**
     * 6️⃣ 更新密碼（bcrypt）
     */
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);

    $updSql = "
        UPDATE MEMBERS
        SET MEMBER_PASSWORD = :pwd
        WHERE MEMBER_ID = :id
        LIMIT 1
    ";
    $upd = $pdo->prepare($updSql);
    $upd->execute([
        ":pwd" => $hash,
        ":id" => $memberId,
    ]);

    json_out(200, [
        "status" => "success",
        "message" => "密碼已更新，請重新登入",
    ]);
} catch (Throwable $e) {
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage(),
    ]);
}
