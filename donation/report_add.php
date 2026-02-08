<?php


// 載入 CORS 設定
require_once("../common/cors.php");

// 載入資料庫連線設定
require_once("../common/conn.php");

// 載入圖片處理工具
require_once("../common/config_upload.php");
// require_once("../common/image_helper.php");

/**
 * 純搬運上傳檔案 (不處理圖片內容)
 * * @param array $uploadedFile $_FILES['image']
 * @param string $uploadDir 上傳目錄路徑
 * @param string $filename 檔案名稱 (不含副檔名)
 * @return array
 */
function handleImageUploadSimple($uploadedFile, $uploadDir, $filename)
{
    // 1. 檢查上傳錯誤
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '檔案上傳失敗: ' . $uploadedFile['error']];
    }

    // 2. 取得原始檔案的副檔名 (例如 .jpg, .png, .webp)
    $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
    $extension = strtolower($extension);

    // 3. 驗證檔案類型 (安全性檢查，防止上傳 .php 等危險檔案)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => '不支援的檔案格式'];
    }

    // 4. 建立上傳目錄
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 5. 組合完整的目標路徑 (保留原副檔名)
    $fullFilename = $filename . '.' . $extension;
    $targetPath = $uploadDir . '/' . $fullFilename;

    // 6. 執行搬運動作 (核心步驟)
    // 
    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        
        // --- 修改這裡 ---
        // 不要用 str_replace($_SERVER['DOCUMENT_ROOT']...)
        // 直接用 pathinfo 或簡單的字串組合來取得最後的檔名與路徑
        
        // 因為我們知道這是在 reports 資料夾下
        $relativePath = 'reports/' . $fullFilename; 

        return [
            'success' => true,
            'path' => $relativePath, // 存入資料庫的會是 reports/xxx.png
            'message' => '上傳成功'
        ];
    } else {
        return ['success' => false, 'message' => '移動檔案失敗，請檢查權限'];
    }
}

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


try {//判斷年分有沒有重複
    $sql = "SELECT COUNT(*) FROM `FINANCIAL_REPORTS` WHERE `DATA_YEAR` = ?";
    $checkStmt = $pdo->prepare($sql);
    $checkStmt->execute([$year]);

    // 使用 fetchColumn() 直接取得 COUNT(*) 的數值
    $count = $checkStmt->fetchColumn();
    if ($count > 0) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "資料年分不可重複"
        ]);
        exit();
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "錯誤：" . $e->getMessage()
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


$uploadResult = handleImageUploadSimple(
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
