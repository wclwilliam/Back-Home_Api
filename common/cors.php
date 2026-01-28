<?php
  $allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500",
    "http://localhost:5173", //本地vue default
    "http://localhost:5174", //本地vue alternate port
    "https://tibamef2e.com", //部屬上伺服器
  ];

  $origin = $_SERVER['HTTP_ORIGIN'] ?? ''; 

  if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    // 必須允許 Content-Type，否則 JSON 請求會失敗
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Credentials: true"); // 如果你需要用到 Cookie/Session
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
    http_response_code(204);
    exit;
  }
?>