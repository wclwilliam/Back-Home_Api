<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 只接受 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '僅接受 POST 請求']);
    exit;
}

// 引入資料庫連線
require_once '../db_connect.php';

try {
    // 接收表單資料
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $content = $_POST['content'] ?? '';
    $admin = $_POST['admin'] ?? '';
    $status = $_POST['status'] ?? 'draft'; // published 或 draft

    // 驗證必填欄位
    if (empty($id) || empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'error' => '缺少必填欄位 (ID/標題/內容)']);
        exit;
    }

    // 處理圖片上傳（如果有新圖片）
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/news/';
        
        // 確保目錄存在
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'error' => '不支援的圖片格式']);
            exit;
        }

        // 生成唯一檔名
        $fileName = 'news_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/news/' . $fileName;
            
            // 刪除舊圖片（如果有的話）
            $stmt = $pdo->prepare("SELECT image_path FROM news WHERE id = ?");
            $stmt->execute([$id]);
            $oldImage = $stmt->fetchColumn();
            
            if ($oldImage && file_exists('../' . $oldImage)) {
                unlink('../' . $oldImage);
            }
        } else {
            echo json_encode(['success' => false, 'error' => '圖片上傳失敗']);
            exit;
        }
    }

    // 更新資料庫
    if ($imagePath) {
        // 有新圖片，更新圖片路徑
        $sql = "UPDATE news 
                SET title = ?, 
                    category = ?, 
                    content = ?, 
                    image_path = ?, 
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $category, $content, $imagePath, $status, $id]);
    } else {
        // 沒有新圖片，不更新圖片欄位
        $sql = "UPDATE news 
                SET title = ?, 
                    category = ?, 
                    content = ?, 
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $category, $content, $status, $id]);
    }

    // 檢查是否更新成功
    if ($stmt->rowCount() > 0 || $pdo->lastInsertId()) {
        echo json_encode([
            'success' => true,
            'message' => $status === 'published' ? '文章已發布' : '草稿已儲存',
            'id' => $id
        ]);
    } else {
        // 即使沒有實際變更，也視為成功（因為資料可能完全相同）
        echo json_encode([
            'success' => true,
            'message' => '更新完成',
            'id' => $id
        ]);
    }

} catch (PDOException $e) {
    error_log("資料庫錯誤: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => '資料庫操作失敗: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("系統錯誤: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => '系統錯誤: ' . $e->getMessage()
    ]);
}
?>