<?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  $db_host = '127.0.0.1';
  $db_port = 8889;
  $db_dbname = 'backHome_db';

  $db_user = 'root';
  $db_password = 'root';

  $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_dbname;charset=utf8mb4";

  try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    
    // 設定資料庫連線時區為 Asia/Taipei (UTC+8)
    $pdo->exec("SET time_zone = '+08:00'");
    
    // 設定 PHP 時區（確保一致）
    date_default_timezone_set('Asia/Taipei');
  } catch (PDOException $e) {
    echo '資料庫連線錯誤：' . $e->getMessage() . '<br>';
    exit();
  }
?>