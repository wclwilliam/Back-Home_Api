<?php

/**
 * 更新救援個案 API
 * 接收表單資料（含選擇性圖片），更新 RESCUES 資料表
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

// ========== 取得 ID 參數 ==========
$reportId = $_POST['id'] ?? null;

if (!$reportId) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "缺少徵信資料 ID"
    ]);
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
        "message" => "請輸入年份"
    ]);
    exit();
}


// ========== 查詢現有資料 ==========
try {
    $checkSql = "SELECT `FILE_PATH` FROM `FINANCIAL_REPORTS` WHERE `FINANCIAL_REPORT_ID` = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$reportId]);
    $existingData = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingData) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "找不到該徵信資料"
        ]);
        exit();
    }

    $oldImagePath = $existingData['FILE_PATH'];
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "資料庫錯誤：" . $e->getMessage()
    ]);
    exit();
}

// ========== 處理圖片更換（如果有上傳新圖片） ==========
$newImagePath = $oldImagePath; // 預設使用舊圖片路徑

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    // 生成檔名：savedcases_{ID}_{timestamp}
    $filename = 'financial_report_' . $reportId . '_' . time();

    // 使用 image_helper 處理圖片
    $uploadResult = handleImageUpload(
        $_FILES['image'],
        UPLOAD_REPORTS_DIR,
        $filename
    );

    if (!$uploadResult['success']) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => $uploadResult['message']
        ]);
        exit();
    }

    $newImagePath = $uploadResult['path'];

    // 刪除舊圖片（如果存在）
    if (!empty($oldImagePath)) {
        $oldImageFullPath = $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/' . $oldImagePath;
        if (file_exists($oldImageFullPath)) {
            @unlink($oldImageFullPath);
        }
    }
}

// ========== 更新資料庫 ==========
try {
    $updateSql = "UPDATE `FINANCIAL_REPORTS` 
                SET `DATA_YEAR` = ?, 
                    `FILE_PATH` = ? 
                WHERE `FINANCIAL_REPORT_ID` = ?";

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        $year,
        $newImagePath,
        $reportId
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "更新失敗：" . $e->getMessage()
    ]);
    exit();
}

// ========== 返回成功結果 ==========
header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "message" => "徵信資料更新成功",
    "imagePath" => $newImagePath
]);

$pdo = null;
exit();
