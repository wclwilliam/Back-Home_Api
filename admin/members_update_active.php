<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證管理員身份
$admin = requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

// 讀 JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Invalid JSON body"], JSON_UNESCAPED_UNICODE);
    exit;
}

$member_id = isset($body['member_id']) ? (int)$body['member_id'] : 0;
$member_active = $body['member_active'] ?? null;

if ($member_id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "member_id is required"], JSON_UNESCAPED_UNICODE);
    exit;
}

// member_active 只允許 0/1
if (!in_array($member_active, [0, 1, '0', '1'], true)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "member_active must be 0 or 1"], JSON_UNESCAPED_UNICODE);
    exit;
}

$member_active = (int)$member_active;

try {
    // 確認會員是否存在
    $stmt = $pdo->prepare("
        SELECT MEMBER_ID
        FROM MEMBERS
        WHERE MEMBER_ID = :id
        LIMIT 1
    ");
    $stmt->execute([":id" => $member_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "member not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ 只更新 MEMBER_ACTIVE（其他欄位一律不碰）
    $stmt2 = $pdo->prepare("
        UPDATE MEMBERS
        SET MEMBER_ACTIVE = :active
        WHERE MEMBER_ID = :id
        LIMIT 1
    ");
    $stmt2->execute([
        ":active" => $member_active,
        ":id" => $member_id
    ]);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "success",
        "member_id" => $member_id,
        "member_active" => $member_active
    ], JSON_UNESCAPED_UNICODE);

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
