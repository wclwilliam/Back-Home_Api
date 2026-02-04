<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("./auth_guard.php"); // 引入驗證機制

header('Content-Type: application/json; charset=utf-8');

try {
    // 驗證身分，取得 member_id
    // 如果沒帶 Token，這裡會直接回傳 401 並結束，前端會收到 error
    $member_id = requireAuth($pdo);

    // 檢查參數
    if (!isset($_GET['activity_id'])) {
        echo json_encode(["status" => "error", "message" => "缺少活動參數"]);
        exit;
    }

    $activity_id = (int)$_GET['activity_id'];

    // 查詢是否已收藏
    $sql = "SELECT COUNT(*) FROM FAVORITES WHERE MEMBER_ID = ? AND ACTIVITY_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$member_id, $activity_id]);
    
    $count = $stmt->fetchColumn();

    echo json_encode([
        "status" => "success", 
        "isFavorite" => $count > 0 
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>