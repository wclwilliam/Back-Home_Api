<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("./auth_guard.php");

header('Content-Type: application/json; charset=utf-8');

// 新增資料通常使用 POST 方法
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        // 接收前端傳來的 JSON 資料
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        
        // 驗證會員身份，取得登入者 member_id
        $member_id = requireAuth($pdo);
        $activity_id = $data['activityId'] ?? null;

        if (!$activity_id) {
            echo json_encode(["status" => "error", "message" => "缺少活動編號"]);
            exit;
        }

        // 使用 INSERT IGNORE 避免重複收藏導致報錯
        $sql = "INSERT IGNORE INTO FAVORITES (MEMBER_ID, ACTIVITY_ID, CREATED_AT) 
                VALUES (:mid, :aid, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mid' => $member_id,
            'aid' => $activity_id
        ]);

        echo json_encode(["status" => "success", "message" => "已加入收藏"]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}