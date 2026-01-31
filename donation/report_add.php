<?php


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
$year = $_POST['year'] ?? '';

// ========== 驗證必填欄位 ==========
if (empty(trim($year))) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "請輸入年分"
    ]);
    exit();
}


// ========== 驗證圖片上傳 ==========
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "請上傳徵信圖片"
    ]);
    exit();
}

// ========== 先插入資料庫取得 ID ==========
try {
    // 取得今天日期（台北時間）
    $uploadDate = date('Y-m-d');

    // 插入基本資料（圖片路徑先留空）
    $sql = "INSERT INTO `FINANCIAL_REPORTS` 
          (`DATA_YEAR`, `FILE_PATH`) 
          VALUES (?, '')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $year
    ]);

    // 取得新增的 ID
    $reportId = $pdo->lastInsertId();
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
$filename = 'financial_report_' . $reportId . '_' . time();

// 使用 image_helper 處理圖片
$uploadResult = handleImageUpload(
    $_FILES['image'],
    UPLOAD_REPORTS_DIR,
    $filename
);

// 如果圖片上傳失敗，刪除剛才新增的記錄
if (!$uploadResult['success']) {
    try {
        $deleteSql = "DELETE FROM `FINANCIAL_REPORTS` WHERE `FINANCIAL_REPORT_ID` = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$reportId]);
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
    $updateSql = "UPDATE `FINANCIAL_REPORTS` SET `FILE_PATH` = ? WHERE `FINANCIAL_REPORT_ID` = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$uploadResult['path'], $reportId]);
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
    "message" => "徵信資料新增成功",
    "id" => $reportId,
    "imagePath" => $uploadResult['path']
]);

$pdo = null;
exit();
