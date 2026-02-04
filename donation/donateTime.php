<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

$member_id = $_GET['memberId'] ?? null;

if (!$member_id) {
    echo json_encode(['status' => 'error', 'message' => '缺少會員 ID']);
    exit;
}

// 1. 修改 SQL：選取所有欄位 (*)，並按時間降序排列，只取最新的一筆
$sql = "SELECT * FROM DONATIONS 
        WHERE MEMBER_ID = :member_id 
        AND DONATION_DATE >= NOW() - INTERVAL 3 MINUTE
        ORDER BY DONATION_DATE DESC 
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['member_id' => $member_id]);

// 2. 使用 fetch() 取代 fetchAll()，因為我們只需要單一物件
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. 判斷是否有抓到資料
if ($result) {
    // 如果有資料，$result 會是該筆資料的關聯陣列
    echo json_encode($result);
} else {
    // 沒資料回傳 false
    echo json_encode(false);
}