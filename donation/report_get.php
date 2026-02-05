<?php
  // 載入 CORS 設定
  require_once("../common/cors.php");
  
  // 載入資料庫連線設定 ($pdo)
  require_once("../common/conn.php");

  if($_SERVER['REQUEST_METHOD'] == "GET"){
    
    // 1. 檢查是否有傳入 id 參數 (例如：api.php?id=5)
    $id = $_GET['id'] ?? null;

    try {
        if ($id) {
            // --- 情況 A：取得單一筆資料 ---
            $sql = "SELECT * FROM `FINANCIAL_REPORTS` WHERE `FINANCIAL_REPORT_ID` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // 使用 fetch 抓取單筆物件，若找不到則回傳 false
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "找不到該筆資料"]);
                exit();
            }
        } else {
            // --- 情況 B：取得所有資料 --- 
            $sql = "SELECT * FROM `FINANCIAL_REPORTS` ORDER BY `DATA_YEAR` DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 輸出 JSON
        header('Content-Type: application/json');
        echo json_encode($data);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "資料庫錯誤：" . $e->getMessage()]);
    }
    
    $pdo = null;
    exit();
  }

  // --- 錯誤處理 ---
  http_response_code(403);
  echo json_encode(["error" => "denied"]);
?>