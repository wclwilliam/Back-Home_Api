<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證管理員身份
$admin = requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// 1) 讀取 ADMIN_ID
$ADMIN_ID = isset($_GET['ADMIN_ID']) ? trim($_GET['ADMIN_ID']) : '';

if ($ADMIN_ID === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["error" => "ADMIN_ID is required"]);
    exit;
}

try {
    // 2) 查單筆（注意：不要回傳 admin_pwd）
    $sql = "
    SELECT
      ADMIN_ID,
      ADMIN_NAME,
      ADMIN_ROLE,
      ADMIN_ACTIVE,
      ADMIN_CREATED_AT,
      ADMIN_LAST_LOGIN_TIME
    FROM ADMIN_USER
    WHERE ADMIN_ID = :admin_id
    LIMIT 1
  ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":admin_id" => $ADMIN_ID]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(["error" => "admin_user not found"]);
        exit;
    }

    // 3) 回傳
    header('Content-Type: application/json');
    echo json_encode(["item" => $row]);

    $pdo = null;
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        "error" => "server_error",
        "message" => $e->getMessage()
    ]);
    exit;
}
