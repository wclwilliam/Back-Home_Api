<?php
// 1. 載入 CORS 與 連線設定
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("./auth_guard.php");

// 確保瀏覽器輸出為 JSON 格式
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    try {
        // 驗證會員身份，取得登入者 member_id
        $member_id = requireAuth($pdo); 

        // 2. 準備 SQL (對應你的 ACTIVITIES 與 FAVORITES 表)
        $sql = "SELECT 
                    f.CREATED_AT as favDate,
                    a.ACTIVITY_ID as activityId,
                    a.ACTIVITY_TITLE as title,
                    a.ACTIVITY_LOCATION as location,
                    a.ACTIVITY_START_DATETIME as startDate,
                    a.ACTIVITY_COVER_IMAGE as image
                FROM FAVORITES f
                JOIN ACTIVITIES a ON f.ACTIVITY_ID = a.ACTIVITY_ID
                WHERE f.MEMBER_ID = :mid
                ORDER BY f.CREATED_AT DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mid' => $member_id]);
        $data = $stmt->fetchAll();

        // 3. 直接輸出結果 (因為 SQL 中已經用 'as' 處理好欄位名稱了)
        echo json_encode($data, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // 抓取 SQL 或連線相關錯誤
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
    exit();
}

// 非 GET 請求的錯誤處理
http_response_code(403);
echo json_encode(["error" => "Method Not Allowed"]);