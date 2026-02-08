<?php
header('Content-Type: application/json');
// 根據你的目錄結構，從 admin 往上一層進入 common 找連線檔
require_once '../common/conn.php'; 
require_once '../common/cors.php'; 

// 取得前端傳來的會員 ID
$member_id = $_GET['member_id'] ?? $_GET['MEMBER_ID'] ?? $_GET['ID'] ?? 0;

if ($member_id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID 錯誤，收到的是: " . json_encode($_GET)]);
    exit;
}

// 修改 SQL：加入 JOIN 語法取得 SUBSCRIPTION 表的 ORDER_ID
// 我們為資料表取別名：D (DONATIONS), S (SUBSCRIPTION) 以方便閱讀
$sql = "SELECT 
            D.TRANSACTION_ID, 
            D.AMOUNT, 
            D.DONATION_DATE, 
            D.PAYMENT_METHOD, 
            D.DONATION_TYPE,
            D.SUBSCRIPTION_ID,
            S.ORDER_ID 
        FROM DONATIONS D
        LEFT JOIN SUBSCRIPTION S ON D.SUBSCRIPTION_ID = S.SUBSCRIPTION_ID
        WHERE D.MEMBER_ID = ? 
        ORDER BY D.DONATION_DATE DESC";

try {
    // 確保使用 conn.php 定義的 $pdo 變數
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$member_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $records
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>