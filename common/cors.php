<?php
  $allowed_origins = [
    "http://127.0.0.1:5500",
    "http://localhost:5500",
    "http://localhost:5173",
    "http://localhost:5174",
    "https://tibamef2e.com",
  ];

  $origin = $_SERVER['HTTP_ORIGIN'] ?? ''; 

  if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    // 必須允許 Content-Type，否則 JSON 請求會失敗
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Credentials: true"); // 如果你需要用到 Cookie/Session
  }

  // --- 重點：處理預檢請求 ---
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // 告知瀏覽器這是一個成功的預檢，然後結束程式
    http_response_code(204); 
    exit;
  }
?>