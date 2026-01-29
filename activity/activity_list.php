<?php
// 1. 載入跨域設定與資料庫連線
  require_once("../common/cors.php");
  require_once("../common/conn.php");

// 2. 設定回傳格式為 JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // 3. 撰寫 SQL：撈出所有「發布中 (STATUS=1)」的活動，並依照舉辦時間排序
    // 我們使用了 JOIN 來把 CATEGORY_VALUE (種類名稱) 一併抓出來
    $sql = "
        SELECT 
            A.*, 
            C.CATEGORY_VALUE 
        FROM ACTIVITIES AS A
        LEFT JOIN ACTIVITY_CATEGORIES AS C ON A.ACTIVITY_CATEGORY_ID = C.CATEGORY_ID
        WHERE A.ACTIVITY_STATUS = 1 
        ORDER BY A.ACTIVITY_START_DATETIME 
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // 4. 抓取資料
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. 輸出 JSON 給 Vue
    echo json_encode([
        'status' => 'success',
        'data' => $activities
    ]);

} catch (PDOException $e) {
    // 如果出錯，回傳錯誤訊息
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>