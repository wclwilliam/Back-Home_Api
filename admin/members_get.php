<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證管理員身份
$admin = requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

// 讀取 member_id
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if ($member_id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "member_id is required"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 查單筆（注意：不要回傳 MEMBER_PASSWORD）
    $sql = "
        SELECT
            MEMBER_ID,
            MEMBER_REALNAME,
            MEMBER_EMAIL,
            MEMBER_PHONE,
            ID_NUMBER,
            BIRTHDAY,
            EMERGENCY,
            EMERGENCY_TEL,
            EMAIL_VERIFIED_AT,
            MEMBER_ACTIVE,
            MEMBER_CREATED_AT
        FROM MEMBERS
        WHERE MEMBER_ID = :member_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":member_id" => $member_id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "member not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["item" => $row], JSON_UNESCAPED_UNICODE);

    $pdo = null;
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "error" => "server_error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
