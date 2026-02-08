<?php
require_once("../common/config_upload.php");

echo "1. 專案根目錄 (DOCUMENT_ROOT): " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "2. 上傳目標目錄 (UPLOAD_REPORTS_DIR): " . UPLOAD_REPORTS_DIR . "<br>";
echo "3. 該目錄是否存在: " . (file_exists(UPLOAD_REPORTS_DIR) ? "是" : "否") . "<br>";
echo "4. 該目錄是否可寫入: " . (is_writable(UPLOAD_REPORTS_DIR) ? "是" : "否") . "<br>";
echo "5. 當前使用者: " . get_current_user() . " (UID: " . getmyuid() . ")<br>";

echo dirname(__DIR__) . '/uploads' . "<br>";

// 測試建立測試檔
$testFile = UPLOAD_REPORTS_DIR . '/test_write.txt';
if (@file_put_contents($testFile, 'test')) {
    echo "6. 寫入測試成功！檔案位置: " . realpath($testFile);
    unlink($testFile); // 刪除測試檔
} else {
    echo "6. 寫入測試失敗！";
}