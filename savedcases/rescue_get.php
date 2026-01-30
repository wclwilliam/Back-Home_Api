<?php
// 載入 CORS 設定
require_once("../common/cors.php");

// 載入資料庫連線設定 ($pdo)
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] == "GET") {

  // 檢查是否有 id 參數（單筆查詢）
  $id = $_GET['id'] ?? null;

  if ($id) {
    // 單筆查詢
    $sql = "SELECT * FROM `RESCUES` WHERE `RESCUE_ID` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $raw_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$raw_data) {
      header('Content-Type: application/json');
      http_response_code(404);
      echo json_encode(["error" => "找不到該救援個案"]);
      $pdo = null;
      exit();
    }

    // 格式化單筆資料
    $formatted_data = [
      "id" => (int)$raw_data['RESCUE_ID'],
      "name" => $raw_data['TURTLE_NAME'],
      "species" => $raw_data['SPECIES'],
      "location" => $raw_data['LOCATION'],
      "description" => $raw_data['STORY_CONTENT'],
      "status" => $raw_data['RECOVERY_STATUS'],
      "imageSrc" => $raw_data['IMAGE_PATH'],
      "uploadDate" => $raw_data['UPLOAD_DATE']
    ];

    header('Content-Type: application/json');
    echo json_encode($formatted_data);
    $pdo = null;
    exit();
  }

  // 全部查詢（原有功能）
  $sql = "SELECT * FROM `RESCUES` ORDER BY `RESCUE_ID` DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute();

  // 1. 先抓取原始資料
  $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 2. 準備一個新陣列來存放轉換後的格式
  $formatted_data = [];

  foreach ($raw_data as $row) {
    $formatted_data[] = [
      "id" => (int)$row['RESCUE_ID'],
      "name" => $row['TURTLE_NAME'],
      "species" => $row['SPECIES'],
      "location" => $row['LOCATION'],
      "description" => $row['STORY_CONTENT'],
      "status" => $row['RECOVERY_STATUS'],
      "imageSrc" => $row['IMAGE_PATH'],
      "uploadDate" => $row['UPLOAD_DATE']
    ];
  }

  // 3. 輸出轉換後的資料
  header('Content-Type: application/json'); // 確保瀏覽器知道這是 JSON
  echo json_encode($formatted_data);

  $pdo = null;
  exit();
}

// --- 錯誤處理 ---
http_response_code(403);
echo json_encode(["error" => "denied"]);
