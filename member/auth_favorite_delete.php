<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

// 刪除資料建議使用 DELETE 方法（或 POST 也可以，看團隊習慣）
if ($_SERVER['REQUEST_METHOD'] == "DELETE" || $_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        
        $member_id = 1;
        $activity_id = $data['activityId'] ?? null;

        if (!$activity_id) {
            echo json_encode(["status" => "error", "message" => "缺少活動編號"]);
            exit;
        }

        $sql = "DELETE FROM FAVORITES WHERE MEMBER_ID = :mid AND ACTIVITY_ID = :aid";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mid' => $member_id,
            'aid' => $activity_id
        ]);

        echo json_encode(["status" => "success", "message" => "已移除收藏"]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}