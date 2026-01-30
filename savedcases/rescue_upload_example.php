<?php

/**
 * Rescue 圖片上傳範例
 * 示範如何使用 image_helper.php
 */

// 載入 CORS 設定
require_once("../common/cors.php");

// 載入資料庫連線
require_once("../common/conn.php");

// 載入圖片處理工具
require_once("../common/image_helper.php");

// ========== 需要定義的變數 ==========

// 1. 上傳目錄 (絕對路徑)
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/savedcases';

// 2. 檢查是否有上傳檔案
if (!isset($_FILES['image'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '未上傳圖片'
    ]);
    exit();
}

// 3. 生成唯一檔案名稱
// 方法 1: 使用時間戳
// $filename = 'savedcases_' . time();

// 方法 2: 使用 uniqid (更安全)
// $filename = 'savedcases_' . uniqid();

// 方法 3: 使用資料庫 ID (如果已經有救援案例 ID) + 時間戳
$filename = 'savedcases_' . $savedcasesId . time();

// ========== 處理圖片上傳 ==========
$result = handleImageUpload($_FILES['image'], $uploadDir, $filename);

// ========== 返回結果 ==========
header('Content-Type: application/json');

if ($result['success']) {
    // 上傳成功，可以將 $result['path'] 儲存到資料庫
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
        'imagePath' => $result['path']  // 例如: /api/uploads/savedcases/savedcases_1234567890.jpg
    ]);
} else {
    // 上傳失敗
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
