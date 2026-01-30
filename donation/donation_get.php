<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD'] == "GET"){
    
    // 使用 JOIN 語法：
    // d.* 代表取捐款表所有欄位，m.email 代表取會員表的 email
    $sql = "SELECT d.*, m.member_email 
            FROM `donations` AS d
            LEFT JOIN `members` AS m ON d.member_id = m.member_id
            ORDER BY d.donation_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($data);
    
    $pdo = null;
    exit();
  }

  http_response_code(403);
  echo json_encode(["error" => "denied"]);
?>