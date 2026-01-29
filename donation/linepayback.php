<?php
require_once("../common/cors.php");
require_once("../common/conn.php"); // 假設你有一個資料庫連線檔

// 1. 取得 LINE Pay 回傳的參數
$transactionId = $_GET['transactionId'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$amount = $_GET['amount'] ?? 0; // 建議從 Session 或資料庫暫存檔取得金額，而非純用 GET
$memberId = $_GET['memberId'] ?? 0; //會員id


if (!$transactionId) {
    echo json_encode(['status' => 'error', 'message' => '缺少交易編號']);
    exit;
}

// 2. LINE Pay API 設定
$channelId = '2008830435';
$channelSecret = 'de84711bbf70b5d282c7ae927323daaf';
$apiUrl = "https://sandbox-api-pay.line.me/v3/payments/$transactionId/confirm";

// 準備 Body (Confirm API 要求金額與幣別必須一致)
$body = [
    'amount' => (int)$amount,
    'currency' => 'TWD'
];

// 3. 簽章加密 (注意：URI 路徑包含 transactionId)
$uri = "/v3/payments/$transactionId/confirm";
$nonce = bin2hex(random_bytes(16));
$jsonBody = json_encode($body);
$authData = $channelSecret . $uri . $jsonBody . $nonce;
$signature = base64_encode(hash_hmac('sha256', $authData, $channelSecret, true));

// 4. 發送 cURL 請求
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-LINE-ChannelId: $channelId",
    "X-LINE-Authorization-Nonce: $nonce",
    "X-LINE-Authorization: $signature"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 本地開發用

$response = curl_exec($ch);
$resData = json_decode($response, true);
curl_close($ch);

// // 建立 Debug 訊息
// $debugMsg = "[" . date('Y-m-d H:i:s') . "] " . 
//             "TID: " . $transactionId . " | " . 
//             "amount: " . $amount . " | " . 
//             "memberId: " . $memberId . " | " . 
//             "OrderID: " . $orderId . " | " .
//             "returnCode: " . $resData['returnCode'] . PHP_EOL;

// 寫入到同目錄下的 confirm_debug.log
// file_put_contents('confirm_debug.log', $debugMsg, FILE_APPEND);

// 5. 判斷是否扣款成功並寫入資料庫
if (isset($resData['returnCode']) && $resData['returnCode'] === '0000') {
    
    // --- 根據圖片結構寫入資料庫 ---
    try {
        $sql = "INSERT INTO donations (
            MEMBER_ID, 
            AMOUNT, 
            DONATION_DATE, 
            SUBSCRIPTION_ID, 
            PAYMENT_METHOD, 
            DONATION_TYPE, 
            TRANSACTION_ID
        ) VALUES (:member_id, :amount, NOW(), :sub_id, :method, :type, :tid)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':member_id' => (int)$memberId, 
            ':amount'    => (int)$amount,
            ':sub_id'    => null,
            ':method'    => 'LINE_PAY',
            ':type'      => '單次捐款',
            ':tid'       => $transactionId 
        ]);

        // 回傳成功資訊給前端 Vue
        echo json_encode([
            'status' => 'success',
            'message' => '捐款成功並已存檔',
            'donation_id' => $pdo->lastInsertId()
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => '資料庫寫入失敗: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'LINE Pay 扣款確認失敗']);
}