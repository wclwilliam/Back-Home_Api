<?php
  // 1. 載入跨域設定與資料庫連線
  require_once("../common/cors.php");
  require_once("../common/conn.php");

  // 2. 檢查是否為 GET 請求
  if($_SERVER['REQUEST_METHOD'] == "GET"){
    
    // --- 新增：判斷模式 (若帶有 ?mode=admin 則視為後台管理模式) ---
    $isAdminMode = isset($_GET['mode']) && $_GET['mode'] === 'admin';

    // --- 原有的：判斷是否為抓取單篇詳細資料 ---
    if(isset($_GET['id']) && !empty($_GET['id'])){
        
        $id = (int)$_GET['id']; // 強制轉為整數確保安全
        
        // 準備 SQL 語法，根據 NEWS_ID 抓取單筆
        // 修改：如果是前台呼叫（非管理模式），必須額外檢查狀態是否為 published
        $sql = "SELECT * FROM `NEWS` WHERE `NEWS_ID` = :id";
        if (!$isAdminMode) {
            $sql .= " AND `NEWS_STATUS` = 'published'";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row){
            // 轉換格式以符合你的資料表設計 (單篇物件)
            $formatted_item = [
                "id" => (int)$row['NEWS_ID'], // 文章編號
                "author_id" => $row['ADMIN_ID'], // 發布管理者帳號
                "title" => $row['NEWS_TITLE'], // 文章標題
                "category" => $row['NEWS_CATEGORY'], // 文章分類
                "published_at" => $row['NEWS_PUBLISHED_AT'], // 發布時間
                "content" => $row['NEWS_CONTENT'], // 文章內容
                "image_path" => $row['NEWS_IMAGE_PATH'], // 文章圖片路徑
                "status" => $row['NEWS_STATUS'] // 文章狀態 (published/draft)
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($formatted_item);
        } else {
            // 找不到資料時回傳 404
            http_response_code(404);
            echo json_encode(["error" => "News not found"]);
        }

    } else {
        //全部清單

        // --- 修改：根據是否為管理模式決定 SQL 語法 ---
        if ($isAdminMode) {
            // 後台模式：抓取所有狀態的新聞
            $sql = "SELECT * FROM `NEWS` ORDER BY `NEWS_PUBLISHED_AT` DESC";
        } else {
            // 前台模式：只抓取 status 為 published 的新聞
            $sql = "SELECT * FROM `NEWS` WHERE `NEWS_STATUS` = 'published' ORDER BY `NEWS_PUBLISHED_AT` DESC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        // 4. 抓取原始扁平資料
        $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. 轉換格式以符合你的資料表設計
        $formatted_data = [];

        foreach ($raw_data as $row) {
            $formatted_data[] = [
                "id" => (int)$row['NEWS_ID'], // 文章編號
                "author_id" => $row['ADMIN_ID'], // 發布管理者帳號
                "title" => $row['NEWS_TITLE'], // 文章標題
                "category" => $row['NEWS_CATEGORY'], // 文章分類
                "published_at" => $row['NEWS_PUBLISHED_AT'], // 發布時間
                "content" => $row['NEWS_CONTENT'], // 文章內容
                "image_path" => $row['NEWS_IMAGE_PATH'], // 文章圖片路徑
                "status" => $row['NEWS_STATUS'] // 文章狀態 (published/draft)
            ];
        }
        
        // 6. 設定 Header 並輸出 JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($formatted_data);
    }
    
    $pdo = null;
    exit();
  }

  // --- 錯誤處理 ---
  http_response_code(403);
  echo json_encode(["error" => "Access denied"]);
?>