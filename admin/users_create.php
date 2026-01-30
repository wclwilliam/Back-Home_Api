<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證管理員身份
$admin = requireAdminAuth();

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

$ADMIN_ID = isset($body['ADMIN_ID']) ? trim($body['ADMIN_ID']) : '';
$ADMIN_NAME = isset($body['ADMIN_NAME']) ? trim($body['ADMIN_NAME']) : '';
$ADMIN_PWD = isset($body['ADMIN_PWD']) ? (string)$body['ADMIN_PWD'] : '';
$ADMIN_ROLE = isset($body['ADMIN_ROLE']) ? trim($body['ADMIN_ROLE']) : '';
$ADMIN_ACTIVE = isset($body['ADMIN_ACTIVE']) ? (int)$body['ADMIN_ACTIVE'] : 1;

// 2) 基本驗證
if ($ADMIN_ID === '' || $ADMIN_NAME === '' || $ADMIN_PWD === '' || $ADMIN_ROLE === '') {
    http_response_code(400);
    echo json_encode(["error" => "ADMIN_ID, ADMIN_NAME, ADMIN_PWD, ADMIN_ROLE are required"]);
    exit;
}

// 可依你們規則調整：帳號/姓名長度限制
if (mb_strlen($ADMIN_ID) > 20 || mb_strlen($ADMIN_NAME) > 50) {
    http_response_code(400);
    echo json_encode(["error" => "ADMIN_ID or ADMIN_NAME is too long"]);
    exit;
}

// 角色白名單
$allowedRoles = ['super', 'general'];
if (!in_array($ADMIN_ROLE, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid ADMIN_ROLE"]);
    exit;
}

// 狀態只允許 0/1
$ADMIN_ACTIVE = ($ADMIN_ACTIVE === 1) ? 1 : 0;

// 密碼基本規範（可自行調整）
if (strlen($ADMIN_PWD) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "password must be at least 6 characters"]);
    exit;
}

try {
    // 3) 檢查 ADMIN_ID 是否重複
    $checkSql = "SELECT 1 FROM ADMIN_USER WHERE ADMIN_ID = :admin_id LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([":admin_id" => $ADMIN_ID]);
    $exists = $checkStmt->fetchColumn();

    if ($exists) {
        http_response_code(409);
        echo json_encode(["error" => "ADMIN_ID already exists"]);
        exit;
    }

    // 4) 密碼雜湊（bcrypt）
    $hash = password_hash($ADMIN_PWD, PASSWORD_BCRYPT);
    if ($hash === false) {
        http_response_code(500);
        echo json_encode(["error" => "failed to hash password"]);
        exit;
    }

    // 5) 寫入資料
    $insertSql = "
    INSERT INTO ADMIN_USER
      (ADMIN_ID, ADMIN_NAME, ADMIN_PWD, ADMIN_ROLE, ADMIN_ACTIVE)
    VALUES
      (:admin_id, :admin_name, :admin_pwd, :admin_role, :admin_active)
  ";

    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ":admin_id" => $ADMIN_ID,
        ":admin_name" => $ADMIN_NAME,
        ":admin_pwd" => $hash,
        ":admin_role" => $ADMIN_ROLE,
        ":admin_active" => $ADMIN_ACTIVE
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
