<?php
declare(strict_types=1);

require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
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
 * 驗證密碼格式：至少 8 字元，包含大小寫英文和數字
 */
function validate_password(string $password): bool
{
    if (strlen($password) < 8) {
        return false;
    }
    // 必須包含大寫字母
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    // 必須包含小寫字母
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    // 必須包含數字
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    return true;
}

// 驗證會員身份，取得登入者 member_id
$memberId = requireAuth($pdo);

// 讀取 JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    json_out(400, ["error" => "Invalid JSON body"]);
}

$newPassword = isset($body['new_password']) ? (string)$body['new_password'] : '';

// 基本驗證
if ($newPassword === '') {
    json_out(400, ["error" => "new_password is required"]);
}

// 驗證新密碼格式
if (!validate_password($newPassword)) {
    json_out(400, ["error" => "new_password must be at least 8 characters and contain uppercase, lowercase letters and numbers"]);
}

try {
    // 雜湊新密碼
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    if ($newPasswordHash === false) {
        json_out(500, ["error" => "failed to hash password"]);
    }

    // 更新密碼
    $updateSql = "
        UPDATE MEMBERS
        SET MEMBER_PASSWORD = :password
        WHERE MEMBER_ID = :member_id
    ";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ":password" => $newPasswordHash,
        ":member_id" => $memberId
    ]);

    json_out(200, [
        "status" => "success",
        "message" => "password updated successfully"
    ]);

} catch (Throwable $e) {
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage()
    ]);
}
