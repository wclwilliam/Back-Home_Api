<?php

/**
 * 圖片裁切與壓縮工具
 * 將上傳的圖片裁切為 4:3 比例並壓縮
 */
/**
 * 裁切並壓縮圖片為指定大小
 * 
 * @param string $sourcePath 原始圖片路徑
 * @param string $targetPath 目標圖片路徑
 * @param int $width 目標寬度 (預設 800)
 * @param int $height 目標高度 (預設 600)
 * @param int $quality 壓縮品質 0-100 (預設 85)
 * @return bool 成功返回 true，失敗返回 false
 */
function resizeAndCropImage($sourcePath, $targetPath, $width = 800, $height = 600, $quality = 85)
{
    // 取得原圖資訊
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        error_log("無法讀取圖片: $sourcePath");
        return false;
    }

    list($srcWidth, $srcHeight, $type) = $imageInfo;

    // 根據圖片類型建立來源圖片資源
    $srcImage = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $srcImage = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = @imagecreatefromwebp($sourcePath);
            break;
        default:
            error_log("不支援的圖片格式: $type");
            return false;
    }

    if (!$srcImage) {
        error_log("無法建立圖片資源");
        return false;
    }

    // 計算裁切區域 (中心裁切)
    $srcRatio = $srcWidth / $srcHeight;   // 原圖比例
    $targetRatio = $width / $height;      // 目標比例 (4:3 = 1.333...)

    if ($srcRatio > $targetRatio) {
        // 原圖太寬 (例如 16:9) → 裁左右
        $newHeight = $srcHeight;
        $newWidth = $srcHeight * $targetRatio;
        $srcX = ($srcWidth - $newWidth) / 2;  // 從中心裁切
        $srcY = 0;
    } else {
        // 原圖太高 (例如 3:4) → 裁上下
        $newWidth = $srcWidth;
        $newHeight = $srcWidth / $targetRatio;
        $srcX = 0;
        $srcY = ($srcHeight - $newHeight) / 2; // 從中心裁切
    }

    // 建立目標圖片資源
    $targetImage = imagecreatetruecolor($width, $height);

    if (!$targetImage) {
        if ($srcImage) imagedestroy($srcImage);
        error_log("無法建立目標圖片資源");
        return false;
    }

    // 處理 PNG 透明度
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $width, $height, $transparent);
    }

    // 裁切並縮放
    $success = imagecopyresampled(
        $targetImage,    // 目標圖片
        $srcImage,       // 來源圖片
        0,
        0,            // 目標圖片起點 (0,0)
        (int)$srcX,
        (int)$srcY,    // 來源圖片裁切起點
        $width,
        $height, // 目標尺寸
        (int)$newWidth,
        (int)$newHeight // 來源裁切尺寸
    );

    if (!$success) {
        if ($srcImage) imagedestroy($srcImage);
        if ($targetImage) imagedestroy($targetImage);
        error_log("圖片處理失敗");
        return false;
    }

    // 儲存圖片
    $saved = false;
    if ($type === IMAGETYPE_PNG) {
        // PNG: quality 0-9, 9 = 最高壓縮
        $pngQuality = 9 - round($quality / 10);
        $saved = imagepng($targetImage, $targetPath, $pngQuality);
    } else {
        // JPG/WebP: quality 0-100
        // 啟用漸進式壓縮（從模糊到清晰載入，檔案更小）
        imageinterlace($targetImage, 1);
        $saved = imagejpeg($targetImage, $targetPath, $quality);
    }

    // 釋放記憶體
    if ($srcImage) imagedestroy($srcImage);
    if ($targetImage) imagedestroy($targetImage);

    if (!$saved) {
        error_log("無法儲存圖片: $targetPath");
        return false;
    }

    return true;
}
/**
 * 處理上傳的圖片
 * 
 * @param array $uploadedFile $_FILES['image'] 上傳的檔案
 * @param string $uploadDir 上傳目錄路徑
 * @param string $filename 檔案名稱 (不含副檔名)
 * @return array ['success' => bool, 'path' => string, 'message' => string]
 */
function handleImageUpload($uploadedFile, $uploadDir, $filename)
{
    // 檢查上傳錯誤
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => '檔案上傳失敗: ' . $uploadedFile['error']
        ];
    }

    // 驗證檔案類型
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($uploadedFile['type'], $allowedTypes)) {
        return [
            'success' => false,
            'message' => '不支援的檔案格式'
        ];
    }

    // 驗證檔案大小 (10MB)
    if ($uploadedFile['size'] > 10 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => '檔案大小超過 10MB'
        ];
    }

    // 建立上傳目錄
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 生成目標檔案路徑 (統一儲存為 .jpg)
    $targetPath = $uploadDir . '/' . $filename . '.jpg';

    // 裁切並壓縮圖片（使用配置檔案的設定）
    $success = resizeAndCropImage(
        $uploadedFile['tmp_name'],  // 臨時檔案
        $targetPath,                // 目標路徑
        IMAGE_WIDTH,                // 使用配置的寬度
        IMAGE_HEIGHT,               // 使用配置的高度
        IMAGE_QUALITY               // 使用配置的品質
    );

    if (!$success) {
        return [
            'success' => false,
            'message' => '圖片處理失敗'
        ];
    }

    // 返回相對路徑 (用於資料庫儲存)
    // 移除 /api/uploads/ 前綴，只保留目錄名稱和檔名
    // 例如：savedcases/savedcases_10_xxx.jpg
    $uploadsBasePath = $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/';
    $relativePath = str_replace($uploadsBasePath, '', $targetPath);

    return [
        'success' => true,
        'path' => $relativePath,
        'message' => '上傳成功'
    ];
}
