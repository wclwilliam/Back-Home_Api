<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/conn.php';
require_once __DIR__ . '/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 验证会员身份，取得登入者 member_id
$member_id = requireAuth($pdo);

// 接收前端 POST 過來的活動 ID
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
$activity_id = $data['activityId'] ?? 0;

// 臨時調試：看看到底收到什麼
if ($activity_id <= 0) {
    echo json_encode([
        "status" => "error", 
        "message" => "無效的活動 ID",
        "debug" => [
            "request_method" => $_SERVER['REQUEST_METHOD'],
            "content_type" => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            "raw_input" => $rawInput,
            "decoded_data" => $data,
            "activity_id" => $activity_id
        ]
    ]);
    exit;
}

try {
    // 更新 CANCEL 為 1 表示已取消
    $sql = "UPDATE ACTIVITY_SIGNUPS 
            SET CANCEL = 1 
            WHERE USER_ID = :member_id AND ACTIVITY_ID = :activity_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id, 'activity_id' => $activity_id]);

    echo json_encode(["status" => "success", "message" => "已成功取消報名"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}