<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

// 只接受 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

// ========================================
// 1. 接收並驗證表單資料
// ========================================
$title    = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$content  = trim($_POST['content'] ?? '');
$admin_id = trim($_POST['admin'] ?? 'admin');

// 驗證必填欄位
if (empty($title) || empty($category) || empty($content)) {
    http_response_code(400);
    echo json_encode(["error" => "標題、分類、內容皆為必填"]);
    exit();
}

// ========================================
// 2. 處理圖片上傳
// ========================================
$image_path = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    
    // 驗證檔案類型
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($_FILES['image']['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(["error" => "僅接受圖片檔案（JPG、PNG、GIF、WEBP）"]);
        exit();
    }

    // 驗證檔案大小（5MB）
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(["error" => "圖片大小不可超過 5MB"]);
        exit();
    }

    // 生成檔名並儲存
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $filename = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
    $upload_dir = __DIR__ . "/uploads/news/";
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
        $image_path = $filename;
    }
}

// ========================================
// 3. 寫入資料庫
// ========================================
$sql = "INSERT INTO `NEWS` (
            `ADMIN_ID`, `NEWS_TITLE`, `NEWS_CATEGORY`, 
            `NEWS_CONTENT`, `NEWS_IMAGE_PATH`, 
            `NEWS_PUBLISHED_AT`, `NEWS_STATUS`
        ) VALUES (
            :admin, :title, :category, 
            :content, :image, 
            NOW(), 'published'
        )";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':admin'    => $admin_id,
    ':title'    => $title,
    ':category' => $category,
    ':content'  => $content,
    ':image'    => $image_path
]);

// ========================================
// 4. 回傳成功訊息
// ========================================
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "success" => true,
    "message" => "新聞發布成功",
    "data" => [
        "id" => (int)$pdo->lastInsertId(),
        "title" => $title,
        "image_path" => $image_path
    ]
]);
?>