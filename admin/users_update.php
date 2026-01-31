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

// 1) 取得 ADMIN_ID（用 query 傳）
$ADMIN_ID = isset($_GET['ADMIN_ID']) ? trim($_GET['ADMIN_ID']) : '';

if ($ADMIN_ID === '') {
    http_response_code(400);
    echo json_encode(["error" => "ADMIN_ID is required"]);
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

$ADMIN_NAME   = isset($body['ADMIN_NAME']) ? trim($body['ADMIN_NAME']) : null;
$ADMIN_ROLE   = isset($body['ADMIN_ROLE']) ? trim($body['ADMIN_ROLE']) : null;
$ADMIN_ACTIVE = isset($body['ADMIN_ACTIVE']) ? (int)$body['ADMIN_ACTIVE'] : null;
$ADMIN_PWD    = isset($body['ADMIN_PWD']) ? (string)$body['ADMIN_PWD'] : null;

try {
    // 3) 確認帳號存在
    $checkStmt = $pdo->prepare(
        "SELECT 1 FROM ADMIN_USER WHERE ADMIN_ID = :admin_id LIMIT 1"
    );
    $checkStmt->execute([":admin_id" => $ADMIN_ID]);

    if (!$checkStmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(["error" => "ADMIN_USER not found"]);
        exit;
    }

    // 4) 動態組 UPDATE 欄位
    $fields = [];
    $params = [":admin_id" => $ADMIN_ID];

    if ($ADMIN_NAME !== null && $ADMIN_NAME !== '') {
        $fields[] = "ADMIN_NAME = :admin_name";
        $params[":admin_name"] = $ADMIN_NAME;
    }

    if ($ADMIN_ROLE !== null && $ADMIN_ROLE !== '') {
        $allowedRoles = ['super', 'general'];
        if (!in_array($ADMIN_ROLE, $allowedRoles, true)) {
            http_response_code(400);
            echo json_encode(["error" => "invalid ADMIN_ROLE"]);
            exit;
        }
        $fields[] = "ADMIN_ROLE = :admin_role";
        $params[":admin_role"] = $ADMIN_ROLE;
    }

    if ($ADMIN_ACTIVE !== null) {
        $fields[] = "ADMIN_ACTIVE = :admin_active";
        $params[":admin_active"] = ($ADMIN_ACTIVE === 1) ? 1 : 0;
    }

    // 5) 密碼：只有「有值且非空字串」才更新
    if ($ADMIN_PWD !== null && $ADMIN_PWD !== '') {
        if (strlen($ADMIN_PWD) < 6) {
            http_response_code(400);
            echo json_encode(["error" => "password must be at least 6 characters"]);
            exit;
        }

        $hash = password_hash($ADMIN_PWD, PASSWORD_BCRYPT);
        if ($hash === false) {
            http_response_code(500);
            echo json_encode(["error" => "failed to hash password"]);
            exit;
        }

        $fields[] = "ADMIN_PWD = :admin_pwd";
        $params[":admin_pwd"] = $hash;
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["error" => "no fields to update"]);
        exit;
    }

    // 6) 執行 UPDATE
    $sql = "
    UPDATE ADMIN_USER
    SET " . implode(", ", $fields) . "
    WHERE ADMIN_ID = :admin_id
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
