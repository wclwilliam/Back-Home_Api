<?php
  require_once("../common/cors.php");
  require_once("../common/conn.php");
  
  // code 1
  if( $_SERVER["REQUEST_METHOD"] == "OPTIONS" ){
    exit();
  }
  
// 2. 設定回傳格式為 JSON
header('Content-Type: application/json');

// 3. 檢查請求方法是否為 POST (或根據需求改為 DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    
    // 取得前端傳來的原始資料並解析 JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // 檢查是否有傳入 ID
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "缺少資料編號 (ID)"]);
        exit();
    }

    $id = intval($data['id']);

    try {
        // 4. 準備 SQL 刪除語法
        $sql = "DELETE FROM `FINANCIAL_REPORTS` WHERE `FINANCIAL_REPORT_ID` = :id";
        $stmt = $pdo->prepare($sql);
        
        // 5. 綁定參數並執行
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // 檢查是否有資料被刪除 (rowCount > 0 代表成功)
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "status" => "success", 
                "message" => "編號 $id 的資料已成功刪除"
            ]);
        } else {
            // 如果 rowCount 為 0，表示資料庫裡找不到這個 ID
            http_response_code(404);
            echo json_encode([
                "status" => "error", 
                "message" => "找不到該筆資料，刪除失敗"
            ]);
        }

    } catch (PDOException $e) {
        // 錯誤處理
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "資料庫錯誤：" . $e->getMessage()]);
    }

    // 關閉連線
    $pdo = null;
    exit();
}

// 非 POST 請求的處理
http_response_code(405);
echo json_encode(["status" => "error", "message" => "不允許的方法"]);
?>