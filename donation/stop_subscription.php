<?php
// 1. 環境設定
// 設定 PHP 處理時間的時區，確保 time() 產生的時間戳記與台灣時間一致
date_default_timezone_set("Asia/Taipei"); 

// 引入外部檔案：
// cors.php: 處理跨網域請求權限（讓 Vue 可以存取這支 PHP）
require_once("../common/cors.php"); 
// conn.php: 建立資料庫連線，通常會產生一個名為 $pdo 的連線物件
require_once("../common/conn.php"); 
// generate_mac_value.php: 處理綠界規範的雜湊加密邏輯
require_once("./generate_mac_value.php"); 

// 2. 接收並解析前端資料
// file_get_contents('php://input')：讀取 Axios 傳來的原始 JSON 字串
$json = file_get_contents('php://input'); 
// json_decode：將 JSON 格式轉成 PHP 的關聯陣列（Array）
$data = json_decode($json, true); 

// 使用 ?? (空接運算子)：如果 data 裡沒有 member_id 就設為 null
$member_id = $data['member_id'] ?? null; 

if (!$member_id) {
    // 回傳 JSON 格式的錯誤訊息給 Vue，並終止程式
    echo json_encode(["status" => "error", "msg" => "缺少會員編號"]);
    exit;
}

try {
    // 3. 資料庫查詢 (使用 PDO 預處理，防止 SQL 注入)
    // ? 是佔位符，代表之後會補上變數
    $sql = "SELECT ORDER_ID, AMOUNT FROM subscription WHERE MEMBER_ID = ? AND STATUS = 1 ORDER BY START_DATE DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    
    // 將變數填入佔位符並執行查詢
    $stmt->execute([$member_id]); 
    // fetch：抓取查詢結果的第一筆資料
    $row = $stmt->fetch(PDO::FETCH_ASSOC); 

    if (!$row) {
        echo json_encode(["status" => "error", "msg" => "找不到進行中的定期定額紀錄"]);
        exit;
    }

    // 將資料庫結果存入變數，方便後續 API 使用
    $order_id = $row['ORDER_ID']; 
    $amount   = $row['AMOUNT']; 

    // 4. 準備發送到綠界的參數
    $ServiceURL = "https://payment-stage.ecpay.com.tw/Cashier/CreditCardPeriodAction";
    $arParameters = [
        "MerchantID"      => $MerchantID,     // 商店編號 (來自 generate_mac_value.php)
        "MerchantTradeNo" => $order_id,       // 原本授權的那筆訂單編號
        "Action"          => "Cancel",        // 官方指定執行動作為「取消」
        "TimeStamp"       => time(),          // 目前的 UNIX 時間戳記
    ];

    // 5. 加密驗證
    // 呼叫你的加密函式產生 CheckMacValue，這是綠界驗證身分最重要的密碼
    $arParameters["CheckMacValue"] = generateCheckMacValue($arParameters);

    // 6. 使用 cURL 發送 HTTPS POST 請求
    $ch = curl_init(); // 初始化 cURL
    curl_setopt($ch, CURLOPT_URL, $ServiceURL); // 設定發送網址
    curl_setopt($ch, CURLOPT_POST, true); // 指定為 POST 方式
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 執行後要將結果傳回變數，而不是直接噴在螢幕上
    // http_build_query：將陣列轉成 A=1&B=2 的網址參數格式
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arParameters)); 
    // 跳過 SSL 憑證檢查（本地 MAMP 測試時必備，以免連線失敗）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $result = curl_exec($ch); // 真正執行連線，並取得綠界回傳的結果

    if ($result === false) {
        die(json_encode(["status" => "error", "msg" => "連線失敗：" . curl_error($ch)]));
    }
    curl_close($ch); // 關閉資源

    // 7. 解析回傳結果
    // parse_str：將綠界回傳的字串格式 (RtnCode=1&RtnMsg=...) 轉為陣列 $response
    parse_str($result, $response);

    // 8. 根據回傳結果進行商業邏輯處理
    // RtnCode == '1' 代表綠界那邊已經成功把該授權停止了
    if (isset($response['RtnCode']) && $response['RtnCode'] == '1') {
        
        // 成功後，同步更新自己資料庫的狀態
        $update_sql = "UPDATE subscription SET STATUS = 0, END_DATE = NOW() WHERE ORDER_ID = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$order_id]);
        
        echo json_encode(["status" => "success", "msg" => "已成功停止定期定額"]);
    } else {
        // 若失敗，將綠界回傳的 RtnMsg (錯誤原因) 傳回前端 Vue
        echo json_encode([
            "status" => "error", 
            "msg" => "綠界拒絕要求：" . ($response['RtnMsg'] ?? "未知錯誤"),
            "debug" => $response
        ]);
    }

} catch (PDOException $e) {
    // 處理資料庫出錯時的情況，避免程式直接噴錯
    echo json_encode(["status" => "error", "msg" => "資料庫連線錯誤：" . $e->getMessage()]);
}