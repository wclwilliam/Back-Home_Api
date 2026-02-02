<?php
require_once("../common/conn.php");
require_once("../common/cors.php"); 

$member_id = $_GET['member_id'];

    // 3. 準備 SQL 查詢
    // 根據您的圖片，過濾 '定期定額' 並依時間降序排列，取第1筆
    $sql = "SELECT * FROM DONATIONS 
            WHERE MEMBER_ID = :member_id 
            AND DONATION_TYPE = '定期定額' 
            ORDER BY DONATION_DATE DESC 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id]);
    $result = $stmt->fetch();

    // 4. 回傳結果
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => '找不到該會員的定期定額紀錄'
        ]);
    }