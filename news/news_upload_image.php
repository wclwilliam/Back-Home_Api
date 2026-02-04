<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_upload.php");
require_once("../common/image_helper.php");

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(405);
    exit(json_encode(["error" => ["message" => "Method not allowed"]]));
}

if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION));
    $pureFilename = time() . "_" . bin2hex(random_bytes(8));
    $upload_dir = __DIR__ . "/../uploads/news/"; // 目標資料夾

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
// 3. 呼叫同學的處理函式
    $result = handleImageUpload($_FILES['upload'], $upload_dir, $pureFilename);

    if ($result['success']) {
        // 4. 直接拼湊對應 Vite VITE_FILE_URL 的路徑
        $finalFileName = $pureFilename . ".jpg";
        
        // 根據你的 MAMP 環境與前端設定，路徑應該是：
        // http://localhost:8888/api/uploads/news/檔名.jpg
        $fullUrl = "http://localhost:8888/api/uploads/news/" . $finalFileName;

        echo json_encode([
            "url" => $fullUrl
        ]);
    } else {
        // 處理失敗的錯誤訊息
        http_response_code(500);
        echo json_encode(["error" => ["message" => $result['message']]]);
    }
}