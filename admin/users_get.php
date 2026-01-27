<?php
require_once("./common/cors.php");
require_once("./common/conn.php");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// 1) 讀取 admin_id
$admin_id = isset($_GET['admin_id']) ? trim($_GET['admin_id']) : '';

if ($admin_id === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["error" => "admin_id is required"]);
    exit;
}

try {
    // 2) 查單筆（注意：不要回傳 admin_pwd）
    $sql = "
    SELECT
      admin_id,
      admin_name,
      admin_role,
      admin_active,
      admin_created_at,
      admin_last_login_time
    FROM admin_user
    WHERE admin_id = :admin_id
    LIMIT 1
  ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":admin_id" => $admin_id]);

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
