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

// 圖片處理邏輯保持不變...
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // ... 原有的圖片驗證與上傳邏輯 ...
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $filename = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
    $upload_dir = __DIR__ . "/../uploads/news/";

    // 2. 檢查資料夾是否存在，不存在則建立 (權限設為 0755)
    if (!is_dir($upload_dir)) {
        // mkdir 的第三個參數 true 代表允許建立多層級目錄
        if (!mkdir($upload_dir, 0755, true)) {
            http_response_code(500);
            echo json_encode(["error" => "無法建立上傳目錄"]);
            exit();
        }
    }

    // 3. 執行檔案移動
    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
        // 存入資料庫的路徑建議只存檔名
        $image_path = $filename;
    } else {
        http_response_code(500);
        echo json_encode(["error" => "檔案移動失敗，請檢查資料夾寫入權限"]);
        exit();
    }
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
    echo json_encode(["error" => "資料庫寫入失敗: " . $e->getMessage()]);
}
