<?php
  // 定義一個陣列，存放你信任的「前端網域」
  // 在開發環境中，Live Server 通常運行在 5500 埠號
  $allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500",
    "http://localhost:5173", //本地vue
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

  // 設定允許的請求標頭（Headers）
  // Content-Type: 允許 JSON 格式
  // Authorization: 允許 JWT token 等認證資訊
  // X-Requested-With: 常見的 AJAX 請求標頭
  header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

  // 允許前端存取回應中的認證資訊（如需要攜帶 Cookie）
  header("Access-Control-Allow-Credentials: true");

  // 設定預檢請求的快取時間（秒）
  header("Access-Control-Max-Age: 3600");

  // 處理 OPTIONS 預檢請求
  // 瀏覽器在發送實際請求前，會先發送 OPTIONS 請求確認是否允許
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
  }
?>