<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

// 獲取 API 傳入的 member_id (假設是透過 GET 或 POST)
$member_id = $_GET['memberId'] ?? null;

if (!$member_id) {
    echo json_encode(['status' => 'error', 'message' => '缺少會員 ID']);
    exit;
}

// 核心 SQL 查詢：判斷最近 3 分鐘內是否有資料
$sql = "SELECT COUNT(*) as recent_count 
        FROM DONATIONS 
        WHERE MEMBER_ID = :member_id 
        AND DONATION_DATE >= NOW() - INTERVAL 3 MINUTE";

$stmt = $pdo->prepare($sql);
$stmt->execute(['member_id' => $member_id]);
$result = $stmt->fetch();

// 回傳結果
if ($result['recent_count'] > 0) {
    echo json_encode([
        'recent_donate' => true
    ]);
} else {
    echo json_encode([
        'recent_donate' => false
    ]);
}