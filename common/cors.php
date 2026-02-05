<?php
$allowed_origins = [
  "http://127.0.0.1:5500",
  "http://localhost:5500",
  "http://localhost:5173",
  "http://localhost:5174",
  "http://localhost:5175",
  "https://tibamef2e.com",
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin && in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: {$origin}");
  header("Vary: Origin"); // ⭐ 很重要：避免快取造成錯誤 origin

  header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

  // 你如果不用 session/cookie，其實可以不開 credentials
  // 但你目前有開也沒關係，只要 Allow-Origin 有明確回即可
  header("Access-Control-Allow-Credentials: true");

  header("Access-Control-Max-Age: 3600");
}

// OPTIONS 預檢
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
