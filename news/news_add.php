<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

// 接收資料，增加 status 判斷
$title    = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$content  = trim($_POST['content'] ?? '');
$admin_id = trim($_POST['admin'] ?? 'admin');
$status   = trim($_POST['status'] ?? 'published'); // 預設為已發布

if (empty($title) || empty($category) || empty($content)) {
    http_response_code(400);
    echo json_encode(["error" => "標題、分類、內容皆為必填"]);
    exit();
}

// 強制檢查圖片是否上傳 (必填)
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "請務必上傳封面圖片"]);
    exit();
}

// 2. 替代 finfo 的格式檢查：使用 getimagesize
$check = getimagesize($_FILES['image']['tmp_name']);
if ($check === false) {
    http_response_code(400);
    echo json_encode(["error" => "檔案不是有效的圖片"]);
    exit();
}

// 限制只允許 JPG 和 PNG (MIME type 判斷)
$allowed_mimes = ['image/jpeg', 'image/png'];
if (!in_array($check['mime'], $allowed_mimes)) {
    http_response_code(400);
    echo json_encode(["error" => "檔案格式錯誤，僅限 JPG, PNG"]);
    exit();
}

// 3. 準備資料夾與檔名 (維持原樣)
$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$filename = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
$upload_dir = __DIR__ . "/../uploads/news/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 4. 執行移動
if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
    $image_path = $filename;
} else {
    http_response_code(500);
    echo json_encode(["error" => "檔案移動失敗"]);
    exit();
}

try {
    // 寫入資料庫，將 :status 參數化
    $db_save_path = $image_path ? "news/" . $image_path : null;
    $sql = "INSERT INTO `NEWS` (
                `ADMIN_ID`, `NEWS_TITLE`, `NEWS_CATEGORY`, 
                `NEWS_CONTENT`, `NEWS_IMAGE_PATH`, 
                `NEWS_PUBLISHED_AT`, `NEWS_STATUS`
            ) VALUES (
                :admin, :title, :category, 
                :content, :image, 
                NOW(), :status
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':admin'    => $admin_id,
        ':title'    => $title,
        ':category' => $category,
        ':content'  => $content,
        ':image'    => $db_save_path,
        ':status'   => $status // 根據前端傳入的值存檔
    ]);

    echo json_encode([
        "success" => true,
        "message" => ($status === 'published' ? "新聞發布成功" : "草稿儲存成功")
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "資料儲存失敗，請確保圖片已正確上傳"]);
}
