<?php

/**
 * 資料庫連線設定
 * 自動偵測本地或遠端環境
 */

// ========== 錯誤處理設定 ==========
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== 時區設定 ==========
date_default_timezone_set("Asia/Taipei");

// ========== 環境偵測與資料庫設定 ==========
if (str_contains($_SERVER["HTTP_HOST"], "127.0.0.1") || str_contains($_SERVER["HTTP_HOST"], "localhost")) {
  // localhost
  $db_host = '127.0.0.1';
  $db_port = 8889;
  $db_dbname = 'backhome_db';

  $db_user = 'root';
  $db_password = 'root';
} else {
  // remote
  $db_host = '127.0.0.1';               // 資料庫主機(ip)
  $db_port = 3306;                      // 資料庫 port number
  $db_dbname = 'tibamefe_cjd102g3'; // 資料庫名稱 (需更改)

  $db_user = 'tibamefe_since2021';      // 可連線資料庫的帳號
  $db_password = 'vwRBSb.j&K#E';        // 該帳號的密碼
}

// ========== 建立資料庫連線 ==========
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_dbname;charset=utf8mb4";

try {
  $pdo = new PDO($dsn, $db_user, $db_password);

  // PDO 屬性設定
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

  // 設定資料庫時區
  $pdo->exec("SET time_zone = '+08:00'");

  // 注意：不要在這裡 echo 任何東西，會破壞 JSON 輸出
  // 如果需要測試連線，請直接訪問這個檔案

} catch (PDOException $e) {
  // 錯誤處理
  header('Content-Type: application/json');
  http_response_code(500);
  echo json_encode([
    "error" => "資料庫連線錯誤",
    "message" => $e->getMessage()
  ]);
  exit();
}
