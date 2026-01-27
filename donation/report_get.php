<?php
  // 載入 CORS 設定
  require_once("../common/cors.php");
  
  // 載入資料庫連線設定 ($pdo)
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD'] == "GET"){
    
    $sql = "SELECT * FROM `financial_reports` ORDER BY 'data_year' DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // 1. 先抓取原始扁平資料
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. 輸出轉換後的資料
    header('Content-Type: application/json'); // 確保瀏覽器知道這是 JSON
    echo json_encode($data);
    
    $pdo = null;
    exit();
  }

  // --- 錯誤處理 ---
  http_response_code(403);
  echo json_encode(["error" => "denied"]);
?>