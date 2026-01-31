<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證會員身份，取得登入者 member_id
$memberId = requireAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 查登入者資料（注意：不要回傳 member_password）
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
            MEMBER_ACTIVE
        FROM MEMBERS
        WHERE MEMBER_ID = :member_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":member_id" => $memberId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "member not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // auth_guard.php 已經檢查 member_active 了，這裡理論上不會進來
    // 保留只是更保險、也比較好讀
    if ((int)$row['MEMBER_ACTIVE'] !== 1) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "account is inactive"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "success",
        "member" => $row
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
