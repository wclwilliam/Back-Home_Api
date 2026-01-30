<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if($_SERVER['REQUEST_METHOD'] == "GET"){
    $member_id = 1; 

    // 只抓取 STATUS = 1 (正常扣款中) 的訂閱
    $sql = "SELECT * FROM subscription 
            WHERE MEMBER_ID = :mid AND STATUS = 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mid' => $member_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>