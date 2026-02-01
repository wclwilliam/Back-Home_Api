<?php

/**
 * 刪除救援個案 API
 * 刪除資料庫記錄並移除相關圖片
 */

// 載入 CORS 設定
require_once("../common/cors.php");

// 載入資料庫連線設定
require_once("../common/conn.php");

// ========== 驗證 DELETE 請求 ==========
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header('Content-Type: application/json');
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "只允許 DELETE 請求"]);
    exit();
}

// ========== 取得 ID 參數（從 URL query string） ==========
$rescueId = $_GET['id'] ?? null;

if (!$rescueId) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "缺少救援個案 ID"
    ]);
    exit();
}

// ========== 查詢現有資料 ==========
try {
    $checkSql = "SELECT `IMAGE_PATH` FROM `RESCUES` WHERE `RESCUE_ID` = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$rescueId]);
    $existingData = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingData) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "找不到該救援個案"
        ]);
        exit();
    }

    $imagePath = $existingData['IMAGE_PATH'];
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "資料庫錯誤：" . $e->getMessage()
    ]);
    exit();
}

// ========== 刪除圖片檔案 ==========
if (!empty($imagePath)) {
    $imageFullPath = $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/' . $imagePath;
    if (file_exists($imageFullPath)) {
        if (!@unlink($imageFullPath)) {
            // 圖片刪除失敗，記錄錯誤但繼續刪除資料庫記錄
            error_log("無法刪除圖片檔案: $imageFullPath");
        }
    }
}

// ========== 刪除資料庫記錄 ==========
try {
    $deleteSql = "DELETE FROM `RESCUES` WHERE `RESCUE_ID` = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$rescueId]);

    // 檢查是否有刪除任何記錄
    if ($deleteStmt->rowCount() === 0) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "救援個案不存在或已被刪除"
        ]);
        exit();
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "刪除失敗：" . $e->getMessage()
    ]);
    exit();
}

// ========== 返回成功結果 ==========
header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "message" => "救援個案已刪除"
]);

$pdo = null;
exit();
