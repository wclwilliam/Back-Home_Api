<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("./auth_guard.php");

if($_SERVER['REQUEST_METHOD'] == "GET"){
    // 驗證會員身份，取得登入者 member_id
    $member_id = requireAuth($pdo); 
    
    // 關聯 subscription 表來確認該筆捐款是否屬於某個定期計畫
    $sql = "SELECT d.*, s.STATUS as sub_status 
            FROM donations d 
            LEFT JOIN subscription s ON d.SUBSCRIPTION_ID = s.SUBSCRIPTION_ID 
            WHERE d.MEMBER_ID = :mid 
            ORDER BY d.DONATION_DATE DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mid' => $member_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>