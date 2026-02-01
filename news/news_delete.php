<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once '../common/conn.php';

try {
    // 獲取 GET 傳來的 ID
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => '缺少 ID']);
        exit;
    }

    // 1. 先找出圖片路徑，準備刪除實體檔案
    $stmtImg = $pdo->prepare("SELECT `NEWS_IMAGE_PATH` FROM `NEWS` WHERE `NEWS_ID` = ?");
    $stmtImg->execute([$id]);
    $imagePath = $stmtImg->fetchColumn();

    if ($imagePath) {
        // 假設你的圖檔存在 ../uploads/news/...
        $fullPath = '../' . $imagePath;
        if (file_exists($fullPath)) {
            unlink($fullPath); // 刪除檔案
        }
    }

    // 2. 執行刪除資料庫紀錄
    $sql = "DELETE FROM `NEWS` WHERE `NEWS_ID` = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => '刪除成功']);
    } else {
        echo json_encode(['success' => false, 'error' => '資料庫刪除失敗']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'SQL錯誤: ' . $e->getMessage()]);
}
