<?php
require_once "../common/conn.php";
require_once '../common/cors.php'; 

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Donation_Report_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 1. 執行查詢取得所有捐款紀錄，並透過 JOIN 取得會員信箱
// 使用 d 和 m 作為別名簡化 SQL 代碼
$sql = "SELECT 
            d.DONATION_ID, 
            d.DONATION_DATE, 
            m.MEMBER_EMAIL, 
            d.TRANSACTION_ID, 
            d.DONATION_TYPE, 
            d.AMOUNT, 
            d.PAYMENT_METHOD 
        FROM `DONATIONS` d
        LEFT JOIN `MEMBERS` m ON d.MEMBER_ID = m.MEMBER_ID 
        ORDER BY d.DONATION_DATE DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$totalAmount = 0;
$rows = [];

// 2. 抓取資料並計算總額
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $totalAmount += (float)$row['AMOUNT'];
    $rows[] = $row;
}

// 3. 寫入總結資訊
fputcsv($output, ['--- 捐款總計統計 ---']);
fputcsv($output, ['匯出時間', date('Y-m-d H:i:s')]);
fputcsv($output, ['總筆數', count($rows)]);
fputcsv($output, ['捐款總額', '$' . number_format($totalAmount, 2)]);
fputcsv($output, []); 

// 4. 寫入明細標題列 (將「會員編號」改為「會員信箱」)
fputcsv($output, ['捐款編號', '捐款時間', '會員信箱', '交易編號', '捐款類型', '捐款金額', '金流類型']);

// 5. 寫入明細資料
foreach ($rows as $row) {
    fputcsv($output, [
        $row['DONATION_ID'],
        $row['DONATION_DATE'],
        $row['MEMBER_EMAIL'], // 現在這裡存放的是關聯出來的信箱
        "\t" . $row['TRANSACTION_ID'], 
        $row['DONATION_TYPE'],
        $row['AMOUNT'],
        $row['PAYMENT_METHOD']
    ]);
}

fclose($output);
exit();