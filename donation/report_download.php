<?php
/**
 * 檔案下載處理程式
 * 功能：接收前端傳來的檔名，驗證存在後強制觸發瀏覽器下載
 */

// 1. 處理跨來源資源共用 (CORS)
// 載入外部設定，確保前端（如 Vue 運行的 localhost:5173）有權限存取此 API
require_once("../common/cors.php");

// 2. 獲取前端傳遞的參數
// 透過 GET 請求取得 URL 中的 file 參數 (例如: ?file=report.png)
$fileName = $_GET['file']; 

// 3. 定義檔案路徑
// 使用相對路徑指向儲存檔案的資料夾
// 注意：這裡的 ../ 是指相對於此 PHP 檔案的上層目錄
$filePath = "../uploads/reports/" . $fileName; 

// 4. 檢查檔案是否存在於伺服器上
if (file_exists($filePath)) {
    
    // --- 開始設定 HTTP 回應標頭 (Headers) ---
    
    // 告知瀏覽器這是一個檔案傳輸任務
    header('Content-Description: File Transfer');
    
    // 設定 MIME 類型為流（Stream），這會告訴瀏覽器這不是一般的網頁，而是二進位檔案
    header('Content-Type: application/octet-stream');
    
    // 最關鍵的一行：設定為 attachment (附件) 並定義下載後的預設檔名
    // basename($filePath) 會過濾掉路徑，只留下檔名部分，防止路徑外洩
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    
    // 快取控制：設定為 0 代表立即過期，確保使用者下載的是最新版本
    header('Expires: 0');
    
    // 告知瀏覽器必須重新驗證快取，不要直接讀取舊有的暫存檔
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // 告知瀏覽器檔案的總大小（單位是 Bytes）
    // 這能讓瀏覽器顯示下載進度條（剩餘百分比/時間）
    header('Content-Length: ' . filesize($filePath));
    
    // --- 執行檔案傳輸 ---
    
    // 清除緩衝區，避免在大檔案下載時佔用過多內存
    flush(); 
    
    // 核心動作：讀取實體檔案內容並直接輸出到 HTTP 回應體中
    readfile($filePath);
    
    // 輸出完成後立即結束程式，避免後續有任何多餘的字元（如空白）被寫入檔案導致損壞
    exit;

} else {
    // 5. 錯誤處理
    // 如果檔案路徑不正確或檔案已被刪除，回傳 404 狀態碼給前端
    http_response_code(404);
    echo "檔案不存在，請確認路徑或檔案名稱是否正確。";
}
?>