<?php
// 1. 設定 Header
header('Content-Type: application/json; charset=utf-8');

// 2. 引入組員寫好的資料庫連線檔案 (請確認檔名與路徑是否正確)
// 假設 db.php 跟這隻檔案在同一個資料夾，或是上一層
require_once('../db.php'); 

try {
    // 3. 獲取登入會員 ID (目前測試先寫死為 1，之後改用 $_SESSION['member_id'])
    $member_id = 1; 

    // 4. 準備 SQL 語句：連結收藏夾與活動主表
    // 這裡使用了你的 SQL 中定義的欄位：FAVORITES(MEMBER_ID, ACTIVITY_ID) 
    // 以及 ACTIVITIES(ACTIVITY_ID, ACTIVITY_TITLE, ACTIVITY_LOCATION, ACTIVITY_COVER_IMAGE)
    $sql = "SELECT 
                f.CREATED_AT AS fav_date,
                a.ACTIVITY_ID,
                a.ACTIVITY_TITLE,
                a.ACTIVITY_LOCATION,
                a.ACTIVITY_START_DATETIME,
                a.ACTIVITY_COVER_IMAGE
            FROM FAVORITES f
            JOIN ACTIVITIES a ON f.ACTIVITY_ID = a.ACTIVITY_ID
            WHERE f.MEMBER_ID = :mid
            ORDER BY f.CREATED_AT DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mid' => $member_id]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. 回傳 JSON 結果給前端
    echo json_encode([
        "status" => "success",
        "data" => $favorites
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "讀取收藏失敗：" . $e->getMessage()
    ]);
}