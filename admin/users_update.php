<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證管理員身份
$admin = requireAdminAuth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// 1) 取得 admin_id（用 query 傳）
$admin_id = isset($_GET['admin_id']) ? trim($_GET['admin_id']) : '';

if ($admin_id === '') {
    http_response_code(400);
    echo json_encode(["error" => "admin_id is required"]);
    exit;
}

// 2) 讀取 JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON body"]);
    exit;
}

$admin_name   = isset($body['admin_name']) ? trim($body['admin_name']) : null;
$admin_role   = isset($body['admin_role']) ? trim($body['admin_role']) : null;
$admin_active = isset($body['admin_active']) ? (int)$body['admin_active'] : null;
$password     = isset($body['password']) ? (string)$body['password'] : null;

try {
    // 3) 確認帳號存在
    $checkStmt = $pdo->prepare(
        "SELECT 1 FROM admin_user WHERE admin_id = :admin_id LIMIT 1"
    );
    $checkStmt->execute([":admin_id" => $admin_id]);

    if (!$checkStmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(["error" => "admin_user not found"]);
        exit;
    }

    // 4) 動態組 UPDATE 欄位
    $fields = [];
    $params = [":admin_id" => $admin_id];

    if ($admin_name !== null && $admin_name !== '') {
        $fields[] = "admin_name = :admin_name";
        $params[":admin_name"] = $admin_name;
    }

    if ($admin_role !== null && $admin_role !== '') {
        $allowedRoles = ['super', 'general'];
        if (!in_array($admin_role, $allowedRoles, true)) {
            http_response_code(400);
            echo json_encode(["error" => "invalid admin_role"]);
            exit;
        }
        $fields[] = "admin_role = :admin_role";
        $params[":admin_role"] = $admin_role;
    }

    if ($admin_active !== null) {
        $fields[] = "admin_active = :admin_active";
        $params[":admin_active"] = ($admin_active === 1) ? 1 : 0;
    }

    // 5) 密碼：只有「有值且非空字串」才更新
    if ($password !== null && $password !== '') {
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["error" => "password must be at least 6 characters"]);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            http_response_code(500);
            echo json_encode(["error" => "failed to hash password"]);
            exit;
        }

        $fields[] = "admin_pwd = :admin_pwd";
        $params[":admin_pwd"] = $hash;
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["error" => "no fields to update"]);
        exit;
    }

    // 6) 執行 UPDATE
    $sql = "
    UPDATE admin_user
    SET " . implode(", ", $fields) . "
    WHERE admin_id = :admin_id
  ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

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
