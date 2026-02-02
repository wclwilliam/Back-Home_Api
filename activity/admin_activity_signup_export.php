<?php
  // admin_activity_signup_export.php

require_once("../common/cors.php");
require_once("../common/conn.php");

// 1. 接收參數
$activityId = isset($_GET['activity_id']) ? (int)$_GET['activity_id'] : 0;

if ($activityId === 0) {
    die("缺少活動 ID");
}

// 2. 查詢活動基本資料
$sql_act = "SELECT ACTIVITY_TITLE
            FROM ACTIVITIES 
            WHERE ACTIVITY_ID = ?";
$stmt_act = $pdo->prepare($sql_act);
$stmt_act->execute([$activityId]);
$activity = $stmt_act->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    die("找不到該活動");
}

// 3. 查詢報名名單
$sql_signup = "
  SELECT 
      ACTIVITY_SIGNUP_ID,
      USER_ID,
      REAL_NAME,
      EMAIL,
      PHONE,
      EMERGENCY,
      EMERGENCY_TEL,
      ATTENDED
  FROM ACTIVITY_SIGNUPS
  WHERE ACTIVITY_ID = ? AND (CANCEL = 0 OR CANCEL IS NULL)
";
$stmt_signup = $pdo->prepare($sql_signup);
$stmt_signup->execute([$activityId]);
$signups = $stmt_signup->fetchAll(PDO::FETCH_ASSOC);

// 4. 準備 CSV 標頭
header('Content-Type: text/csv; charset=utf-8');
//檔名
$filename = "activity_{$activityId}_signups_" . date('Ymd') . ".csv";
// 下載
header('Content-Disposition: attachment; filename=\"' . $filename . '\"');

// 輸出
$output = fopen('php://output', 'w');

// 加上 BOM 碼，讓 Excel 知道是 UTF-8 編碼
fputs($output, "\xEF\xBB\xBF");

// 設定 CSV 欄位名稱
$header = [
    '報名ID', 
    '會員ID', 
    '姓名', 
    'Email', 
    '手機號碼', 
    '緊急聯絡人',
    '緊急聯絡人電話',
    '出席'
];
fputcsv($output, $header);

// 5. 寫入報名資料
foreach ($signups as $row) {
    $data = [
        $row['ACTIVITY_SIGNUP_ID'],
        $row['USER_ID'],
        $row['REAL_NAME'],
        $row['EMAIL'],
        $row['PHONE'],
        $row['EMERGENCY'],
        $row['EMERGENCY_TEL'],
        $row['ATTENDED']
    ];
    fputcsv($output, $data);
}

fclose($output);

?>