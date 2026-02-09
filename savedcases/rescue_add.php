<?php

/**
 * 新增救援個案 API
 * 接收表單資料（含圖片），新增至 RESCUES 資料表
 */

// 載入 CORS 設定
require_once("../common/cors.php");

// 載入資料庫連線設定
require_once("../common/conn.php");

// 載入圖片處理工具
require_once("../common/config_upload.php");
require_once("../common/image_helper.php");

// ========== 驗證 POST 請求 ==========
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(["error" => "denied"]);
    exit();
}

// ========== 接收表單資料 ==========
$name = $_POST['name'] ?? '';
$species = $_POST['species'] ?? '';
$location = $_POST['location'] ?? '';
$status = $_POST['status'] ?? '';
$description = $_POST['description'] ?? '';

// ========== 驗證必填欄位 ==========
if (empty(trim($name))) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "請輸入海龜姓名"
    ]);
    exit();
}

if (empty(trim($location))) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "請輸入發現地點"
    ]);
    exit();
}

if (empty(trim($description))) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "請輸入受傷原因與故事文案"
    ]);
    exit();
}

// ========== 驗證圖片上傳 ==========
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "請上傳海龜照片"
    ]);
    exit();
}

// ========== 先插入資料庫取得 ID ==========
try {
    // 取得上傳日期（優先使用前端傳來的，若無則使用當下完整的 Y-m-d H:i:s）
    $uploadDate = $_POST['uploadDate'] ?? date('Y-m-d H:i:s');

    // 插入基本資料（圖片路徑先留空）
    $sql = "INSERT INTO `RESCUES` 
          (`TURTLE_NAME`, `SPECIES`, `LOCATION`, `RECOVERY_STATUS`, `STORY_CONTENT`, `UPLOAD_DATE`, `IMAGE_PATH`) 
          VALUES (?, ?, ?, ?, ?, ?, '')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name,
        $species,
        $location,
        $status,
        $description,
        $uploadDate
    ]);

    // 取得新增的 ID
    $rescueId = $pdo->lastInsertId();
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "資料庫錯誤：" . $e->getMessage()
    ]);
    exit();
}

// ========== 處理圖片上傳 ==========
// 生成檔名：savedcases_{ID}_{timestamp}
$filename = 'savedcases_' . $rescueId . '_' . time();

// 使用 image_helper 處理圖片
$uploadResult = handleImageUpload(
    $_FILES['image'],
    UPLOAD_RESCUES_DIR,
    $filename
);

// 如果圖片上傳失敗，刪除剛才新增的記錄
if (!$uploadResult['success']) {
    try {
        $deleteSql = "DELETE FROM `RESCUES` WHERE `RESCUE_ID` = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$rescueId]);
    } catch (PDOException $e) {
        // 刪除失敗也要回報
    }

    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $uploadResult['message']
    ]);
    exit();
}

// ========== 更新圖片路徑 ==========
try {
    $updateSql = "UPDATE `RESCUES` SET `IMAGE_PATH` = ? WHERE `RESCUE_ID` = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$uploadResult['path'], $rescueId]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "更新圖片路徑失敗：" . $e->getMessage()
    ]);
    exit();
}

// ========== 返回成功結果 ==========
header('Content-Type: application/json');
http_response_code(201);
echo json_encode([
    "success" => true,
    "message" => "救援個案新增成功",
    "id" => $rescueId,
    "imagePath" => $uploadResult['path']
]);

$pdo = null;
exit();
