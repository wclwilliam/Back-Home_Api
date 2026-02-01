<?php

/**
 * 上傳配置檔案
 * 定義所有上傳相關的常數和路徑
 */

// ========== 上傳目錄配置 ==========

// 救援案例圖片目錄（實際儲存位置 - 絕對路徑）
define('UPLOAD_RESCUES_DIR', $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/savedcases');

// 活動圖片目錄
define('UPLOAD_ACTIVITIES_DIR', $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/actCover');

// 最新消息圖片目錄
define('UPLOAD_NEWS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/news');

// 徵信資料圖片目錄
define('UPLOAD_REPORTS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/reports');

// ========== 圖片設定 ==========

// 最大檔案大小 (10MB)
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// 允許的圖片格式
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);

// 圖片尺寸設定
define('IMAGE_WIDTH', 800);
define('IMAGE_HEIGHT', 600);
define('IMAGE_QUALITY', 65);  // 降低品質以獲得更好的壓縮率（65 適合網頁展示）

// ========== 初始化函數 ==========

/**
 * 確保所有上傳目錄存在
 */
function initializeUploadDirectories()
{
    $dirs = [
        UPLOAD_RESCUES_DIR,
        UPLOAD_ACTIVITIES_DIR,
        UPLOAD_NEWS_DIR
    ];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// 自動初始化目錄
initializeUploadDirectories();
