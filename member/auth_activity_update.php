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

$data = json_decode(file_get_contents("php://input"), true);
$activity_id = $data['activityId'] ?? 0;

// ACTIVITY_SIGNUPS 表中可更新的欄位（根據實際需求選擇）
$real_name = $data['realName'] ?? null;
$phone = $data['phone'] ?? null;
$email = $data['email'] ?? null;
$emergency = $data['emergency'] ?? null;
$emergency_tel = $data['emergencyTel'] ?? null;

if ($activity_id <= 0) {
    echo json_encode([
        "status" => "error", 
        "message" => "參數錯誤",
        "debug" => [
            "received_data" => $data,
            "activity_id" => $activity_id
        ]
    ]);
    exit;
}

try {
    // 更新報名資料（根據需要調整要更新的欄位）
    $sql = "UPDATE ACTIVITY_SIGNUPS 
            SET REAL_NAME = COALESCE(:real_name, REAL_NAME),
                PHONE = COALESCE(:phone, PHONE),
                EMAIL = COALESCE(:email, EMAIL),
                EMERGENCY = COALESCE(:emergency, EMERGENCY),
                EMERGENCY_TEL = COALESCE(:emergency_tel, EMERGENCY_TEL)
            WHERE USER_ID = :member_id AND ACTIVITY_ID = :activity_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'real_name' => $real_name,
        'phone' => $phone,
        'email' => $email,
        'emergency' => $emergency,
        'emergency_tel' => $emergency_tel,
        'member_id' => $member_id,
        'activity_id' => $activity_id
    ]);

    echo json_encode(["status" => "success", "message" => "報名資料已更新"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}