<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// 1) 讀取 JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON body"]);
    exit;
}

$admin_id = isset($body['admin_id']) ? trim($body['admin_id']) : '';
$admin_name = isset($body['admin_name']) ? trim($body['admin_name']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';
$admin_role = isset($body['admin_role']) ? trim($body['admin_role']) : '';
$admin_active = isset($body['admin_active']) ? (int)$body['admin_active'] : 1;

// 2) 基本驗證
if ($admin_id === '' || $admin_name === '' || $password === '' || $admin_role === '') {
    http_response_code(400);
    echo json_encode(["error" => "admin_id, admin_name, password, admin_role are required"]);
    exit;
}

// 可依你們規則調整：帳號/姓名長度限制
if (mb_strlen($admin_id) > 20 || mb_strlen($admin_name) > 50) {
    http_response_code(400);
    echo json_encode(["error" => "admin_id or admin_name is too long"]);
    exit;
}

// 角色白名單
$allowedRoles = ['super', 'general'];
if (!in_array($admin_role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid admin_role"]);
    exit;
}

// 狀態只允許 0/1
$admin_active = ($admin_active === 1) ? 1 : 0;

// 密碼基本規範（可自行調整）
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "password must be at least 6 characters"]);
    exit;
}

try {
    // 3) 檢查 admin_id 是否重複
    $checkSql = "SELECT 1 FROM admin_user WHERE admin_id = :admin_id LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([":admin_id" => $admin_id]);
    $exists = $checkStmt->fetchColumn();

    if ($exists) {
        http_response_code(409);
        echo json_encode(["error" => "admin_id already exists"]);
        exit;
    }

    // 4) 密碼雜湊（bcrypt）
    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        http_response_code(500);
        echo json_encode(["error" => "failed to hash password"]);
        exit;
    }

    // 5) 寫入資料
    $insertSql = "
    INSERT INTO admin_user
      (admin_id, admin_name, admin_pwd, admin_role, admin_active)
    VALUES
      (:admin_id, :admin_name, :admin_pwd, :admin_role, :admin_active)
  ";

    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ":admin_id" => $admin_id,
        ":admin_name" => $admin_name,
        ":admin_pwd" => $hash,
        ":admin_role" => $admin_role,
        ":admin_active" => $admin_active
    ]);

    echo json_encode(["ok" => true]);
    $pdo = null;
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "server_error",
        "message" => $e->getMessage()
    ]);
    exit;
}
