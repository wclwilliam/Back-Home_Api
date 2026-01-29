<?php
/**
 * LINE Pay Online API v3 - Request 階段
 * 作用：接收前端訂單資訊，向 LINE Pay 申請付款連結
 */

// 1. 載入跨域 (CORS) 設定，允許 Vue3 從不同連接埠存取
// 這邊通常包含 Access-Control-Allow-Origin 等 Headers 的處理
require_once("../common/cors.php");

// 2. LINE Pay Sandbox 測試環境參數設定
// 提醒：正式上線時需更換為正式機的 ID 與 Secret
$channelId = '2008830435'; 
$channelSecret = 'de84711bbf70b5d282c7ae927323daaf'; 
$apiUrl = 'https://sandbox-api-pay.line.me/v3/payments/request';

// 3. 接收並解析從 Vue3 (axios.post) 傳來的 JSON 資料
// php://input 是讀取 HTTP 請求 Body 的原始數據流
$input = file_get_contents('php://input');
$data = json_decode($input, true); // 將 JSON 字串轉為 PHP 關聯陣列

// 準備訂單基本資訊
$orderId = 'ORDER_' . date('YmdHis'); // 產生唯一訂單編號（範例用時間戳記）
$amount = $data['amount'] ?? 100;      // 若前端沒傳金額，預設為 100
$productName = $data['productName'] ?? '測試商品';

// 路徑判斷
if (str_contains($_SERVER["HTTP_HOST"], "127.0.0.1") || str_contains($_SERVER["HTTP_HOST"], "localhost")) {
  //本地
    $myurl = "http://localhost:5173/donation";
} else {
  //伺服器上
    $myurl = "https://tibamef2e.com/cjd102/g3/front/donation";
};

/**
 * 4. 依照 LINE Pay 官方規格建立請求主體 (Request Body)
 * 金額 (amount) 必須為整數型別 (int)
 */
$body = [
    'amount' => (int)$amount,
    'currency' => 'TWD',
    'orderId' => $orderId,
    'packages' => [[
        'id' => 'package_1',
        'amount' => (int)$amount,
        'name' => '海龜保育', // 商店名稱
        'products' => [[
            'name' => $productName,
            'quantity' => 1,
            'price' => (int)$amount
        ]]
    ]],
    'redirectUrls' => [
        // 使用者在 LINE Pay 頁面操作完後，要導回前端 Vue 的哪一個頁面
        'confirmUrl' => "$myurl?amount=$amount", // 成功導回點
        'cancelUrl' => $myurl   // 取消導回點
    ]
];

// 5. LINE Pay V3 核心：HMAC-SHA256 簽章邏輯
// V3 規定 Header 必須包含 Authorization 欄位來驗證身分
$uri = '/v3/payments/request';        // API 的路徑（不含域名）
$nonce = bin2hex(random_bytes(16));   // 隨機字串，確保每次請求的簽章都不同，防止重放攻擊
$jsonBody = json_encode($body);       // 將陣列轉回 JSON，簽章加密需要用到這串字

// 簽章公式：Channel Secret + API Path + JSON Body + Nonce
$authData = $channelSecret . $uri . $jsonBody . $nonce;

// 使用 HMAC-SHA256 演算法，並將結果轉為 Base64 編碼
$signature = base64_encode(hash_hmac('sha256', $authData, $channelSecret, true));

// 6. 初始化 cURL 執行連線任務
$ch = curl_init($apiUrl);

// 設定 cURL 選項
curl_setopt($ch, CURLOPT_POST, true);           // 設定發送方式為 POST
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 將 API 回傳結果存入變數，而非直接印出
curl_setopt($ch, CURLOPT_HTTPHEADER, [          // 設定 LINE Pay 要求的標頭
    "Content-Type: application/json",
    "X-LINE-ChannelId: $channelId",
    "X-LINE-Authorization-Nonce: $nonce",
    "X-LINE-Authorization: $signature"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody); // 放進請求的 JSON 資料

/**
 * 7. SSL 安全驗證設定 (針對 MAMP 環境優化)
 * $origin 通常在 cors.php 裡面定義過，若無則需手動定義
 */
$isLocal = (!isset($origin) || strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false);

if ($isLocal) {
    // 本地環境：忽略 SSL 憑證檢查（解決 MAMP 連線失敗問題）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
} else {
    // 正式環境：開啟檢查以確保通訊安全
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
}

// 8. 執行請求並取得回應
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 取得 HTTP 狀態碼 (例如 200)
curl_close($ch); // 關閉 cURL 連線

// 9. 回傳結果給前端 Vue3
header('Content-Type: application/json');

if ($httpCode === 200) {
    $result = json_decode($response, true);
    // returnCode "0000" 代表 LINE Pay 成功受理請求
    if ($result['returnCode'] === '0000') {
        echo json_encode([
            'status' => 'success',
            // 這個 paymentUrl.web 就是要讓前端 window.location.href 跳轉的網址
            'paymentUrl' => $result['info']['paymentUrl']['web']
        ]);
    } else {
        // 簽章錯、金額錯、或其他 LINE Pay 定義的錯誤
        echo json_encode([
            'status' => 'error',
            'message' => $result['returnMessage']
        ]);
    }
} else {
    // cURL 連線失敗 (例如 404, 500 或是網路斷線)
    echo json_encode([
        'status' => 'error',
        'message' => 'LINE Pay API 連線失敗',
        'debug' => $response
    ]);
}