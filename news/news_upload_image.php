<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(405);
    exit(json_encode(["error" => ["message" => "Method not allowed"]]));
}

if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION));
    // 使用與 news_add.php 相同的命名規則
    $filename = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
    $upload_dir = __DIR__ . "/../uploads/news/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($_FILES['upload']['tmp_name'], $upload_dir . $filename)) {
        // 重要：回傳給 CKEditor 的路徑要對應你的 VITE_FILE_URL
        // 因為你的 VITE_FILE_URL 結尾是 /uploads/，所以這裡回傳 news/檔名
        $relativePath = "news/" . $filename;
        
        // 為了讓 CKEditor 預覽成功，這裡仍需組合一個暫時的完整網址
        // 之後存入資料庫的內容就會是這個網址
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'];
        
        // 這裡手動拼湊出符合 VITE_FILE_URL 結構的完整網址
        $fullUrl = $protocol . $domain . "/cjd102/g3/api/uploads/" . $relativePath;

        echo json_encode([
            "url" => $fullUrl
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => ["message" => "檔案移動失敗"]]);
    }
}
?>