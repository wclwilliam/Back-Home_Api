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

// 根據 SQL 結構：使用 TRANSACTION_ID, DONATION_TYPE 
$sql = "SELECT 
            TRANSACTION_ID, 
            AMOUNT, 
            DONATION_DATE, 
            PAYMENT_METHOD, 
            DONATION_TYPE,
            SUBSCRIPTION_ID
        FROM DONATIONS 
        WHERE MEMBER_ID = ? 
        ORDER BY DONATION_DATE DESC";

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