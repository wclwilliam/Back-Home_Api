<?php
// 允許來自 http://localhost:5173 的請求
header("Access-Control-Allow-Origin: http://localhost:5173");
// 允許的請求方法
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
// 允許的請求標頭 (如果你有傳 Token，這裡一定要包含 Authorization)
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 處理 Preflight (OPTIONS) 請求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit; // 預檢請求直接回傳 200 即可
}


// 引入資料庫連線 (請確認路徑正確)
  require_once("../common/cors.php");
  require_once("../common/conn.php");

try {
    // 1. 接收前端傳來的資料
    $id       = $_POST['id'] ?? '';
    $title    = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $content  = $_POST['content'] ?? '';
    $status   = $_POST['status'] ?? 'draft';

    // 2. 驗證必填
    if (empty($id) || empty($title)) {
        echo json_encode(['success' => false, 'error' => '缺少 ID 或標題']);
        exit;
    }

    // 2. 先查詢資料庫中「目前的狀態」
    $checkSql = "SELECT `NEWS_STATUS` FROM `NEWS` WHERE `NEWS_ID` = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$id]);
    $currentRecord = $checkStmt->fetch();

    if (!$currentRecord) {
        echo json_encode(['success' => false, 'error' => '找不到該文章']);
        exit;
    }

    $currentStatus = $currentRecord['NEWS_STATUS'];

    // 💡 核心邏輯：如果目前是草稿，就更新時間；如果是已發布，就維持原樣
    $timeUpdateSql = "";
    if ($currentStatus === 'draft') {
        $timeUpdateSql = ", `NEWS_PUBLISHED_AT` = NOW() ";
    }

    // 3. 處理圖片上傳 (維持你原本的邏輯)
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/news/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $fileName = 'news_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'news/' . $fileName; // 配合你資料庫的格式
        }
    }

    // 4. 正確的 SQL 語句 (欄位名稱必須與截圖中的大寫一致)
    if ($imagePath) {
        // 有新圖片時的更新
        $sql = "UPDATE `NEWS` 
                SET `NEWS_TITLE` = ?, 
                    `NEWS_CATEGORY` = ?, 
                    `NEWS_CONTENT` = ?, 
                    `NEWS_IMAGE_PATH` = ?, 
                    `NEWS_STATUS` = ?
                    $timeUpdateSql
                WHERE `NEWS_ID` = ?";
        $stmt = $pdo->prepare($sql);
        $params = [$title, $category, $content, $imagePath, $status, $id];
    } else {
        // 沒有新圖片時，不要去動 NEWS_IMAGE_PATH
        $sql = "UPDATE `NEWS` 
                SET `NEWS_TITLE` = ?, 
                    `NEWS_CATEGORY` = ?, 
                    `NEWS_CONTENT` = ?, 
                    `NEWS_STATUS` = ?
                    $timeUpdateSql
                WHERE `NEWS_ID` = ?";
        $stmt = $pdo->prepare($sql);
        $params = [$title, $category, $content, $status, $id];
    }

    if ($stmt->execute($params)) {
        // 取得當前時間回傳給前端同步畫面
        $now = date("Y-m-d H:i:s");
        echo json_encode([
            'success' => true, 
            'message' => '更新成功',
            'debug_id' => $id 
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => '資料庫更新失敗']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'SQL錯誤: ' . $e->getMessage()]);
}
?>