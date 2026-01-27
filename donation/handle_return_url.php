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
      // 資料庫的資料更新
      // $sql = "UPDATE orders SET ecpay_data = ? WHERE order_id = ?";
      // $stmt = $pdo->prepare($sql);
      // $stmt->execute([json_encode($_POST), $_POST["MerchantTradeNo"]]);

      // 判斷是單次還是定期定額 (檢查是否有定期定額特有欄位 TotalSuccessTimes)
      $is_period = isset($_POST["TotalSuccessTimes"]);
      $type = $is_period ? "定期定額" : "單次捐款";

      $sql = "INSERT INTO donations( 
      donation_id
      member_id, 
      amount,
      subscription_id,
      payment_method,
      donation_type,
      transaction_id 
      ) VALUES (?, ?, ?, ?, ?, ?,?)";
      $stmt = $pdo->prepare($sql);
      // $products_str = json_encode($products);
      $stmt->execute([
        $_POST["MerchantTradeNo"],
        10, //暫時寫死
        $_POST["TradeAmt"],
        $is_period ? $_POST["MerchantTradeNo"] : NULL,
        "信用卡",
        $type,$_POST["TradeNo"]
        ]);
      
      echo "1|OK"; // 傳 1|OK 給綠界
    }else{
      echo "0|NOTOK"; // 隨意傳錯誤的資料給綠界
    }
    
  } else {
    echo "請使用 POST 方法提交資料。";
  }
?>