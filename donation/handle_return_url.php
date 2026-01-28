<?php
  date_default_timezone_set("Asia/Taipei");

  require_once("../common/conn.php");
  // 檢查是否有 POST 資料
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once("./generate_mac_value.php");

    // 將 ecpay 回傳的資料，放在純文字檔，方便看資料
    $filename = __DIR__ . '/ecpay_data.txt'; // 純文字檔
    $timestamp = date('Y-m-d H:i:s'); // 取得當前時間
    $postData = print_r($_POST, true); // 將 $_POST 資料轉為字串格式
    $content = "==== $timestamp ====\n$postData\n"; // 準備要寫入檔案的內容(包含時間)
    file_put_contents($filename, $content, FILE_APPEND | LOCK_EX); // 將內容寫入檔案(放在最後面)
    // 若確定不需要再看資料了，可移除上面五行

    // 檢查 MacValue
    $ecpayMacValue = $_POST["CheckMacValue"];
    unset($_POST["CheckMacValue"]);
    $MacValue = generateCheckMacValue($_POST);

    // 回應訊息
    if($MacValue == $ecpayMacValue){

      try {
        $is_period = isset($_POST["TotalSuccessTimes"]);
        $type = $is_period ? "定期定額" : "單次捐款";

        // 1. 移除 DONATION_ID (讓 AUTO_INCREMENT 處理)
        // 2. 增加 DONATION_DATE 欄位 (補上 NOW())
        // 3. 確保欄位數量為 7 個
        $sql = "INSERT INTO donations ( 
                    MEMBER_ID, 
                    AMOUNT,
                    DONATION_DATE,
                    SUBSCRIPTION_ID,
                    PAYMENT_METHOD,
                    DONATION_TYPE,
                    TRANSACTION_ID 
                ) VALUES (?, ?, NOW(), ?, ?, ?, ?)";
                
        $stmt = $pdo->prepare($sql);
        
        // 這裡要注意：如果 SUBSCRIPTION_ID 在 DB 是 int，傳字串進去會變 0 或出錯
        // 暫時建議先將 SUBSCRIPTION_ID 設為 NULL 或確保資料表已改為 varchar
        $subscription_val = $is_period ? 12345 : null; // 測試用，之後建議改 DB 類型

        $stmt->execute([
            10,                         // MEMBER_ID
            $_POST["TradeAmt"],        // AMOUNT
            $subscription_val,          // SUBSCRIPTION_ID (注意 DB 類型!)
            "信用卡",                   // PAYMENT_METHOD
            $type,                      // DONATION_TYPE
            $_POST["TradeNo"]           // TRANSACTION_ID (綠界交易序號)
        ]);

        echo "1|OK";

    } catch (PDOException $e) {
        // 這一行非常重要！如果還是失敗，請打開 ecpay_data.txt 看最底下的錯誤訊息
        file_put_contents($filename, "SQL Error: " . $e->getMessage() . "\n", FILE_APPEND);
        echo "0|Error";
    }
    }
    
  } else {
    echo "請使用 POST 方法提交資料。";
  }
?>