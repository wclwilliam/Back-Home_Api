<?php
  // 定義一個陣列，存放你信任的「前端網域」
  // 在開發環境中，Live Server 通常運行在 5500 埠號
  $allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500",
    "http://localhost:5173", //本地vue default
    "http://localhost:5174", //本地vue alternate port
    "https://tibamef2e.com", //部屬上伺服器
  ];

  // $_SERVER['HTTP_ORIGIN'] 會自動抓取「發出請求的那個網頁地址」
  // 如果是直接在瀏覽器輸入網址，通常沒有 Origin，所以用 ?? '' 給予空字串避免報錯
  $origin = $_SERVER['HTTP_ORIGIN'] ?? ''; 

  // 檢查目前這個請求的來源 (Origin) 是否在我們定義的「白名單」陣列中
  if (in_array($origin, $allowed_origins)) {
    // 如果匹配成功，就發送一個 Header 給瀏覽器
    // 告訴瀏覽器：「我允許這個網域存取我的資料」
    header("Access-Control-Allow-Origin: " . $origin);
  }

  // 設定允許前端使用的 HTTP 方法
  // 包含常見的讀取(GET)、新增(POST)、修改(PATCH/PUT)、刪除(DELETE)
  // OPTIONS 則是用於「預檢請求 (Preflight Request)」
  header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
?>