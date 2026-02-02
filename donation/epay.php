<?php
  date_default_timezone_set("Asia/Taipei");

  if(isset($_POST["UseEcpay"]) && $_POST["UseEcpay"] == "ecpay"){
    require_once("../common/conn.php");
    require_once("./generate_mac_value.php");

    // 路徑判斷
    if (str_contains($_SERVER["HTTP_HOST"], "127.0.0.1") || str_contains($_SERVER["HTTP_HOST"], "localhost")) {
      //本地
        $backurl = "https://undelighted-unadhesively-elenor.ngrok-free.dev/api/donation";
        $fronturl = "http://localhost:5173/donation";
    } else {
      //伺服器上
        $backurl = "https://tibamef2e.com/cjd102/g3/api/donation";
        $fronturl = "https://tibamef2e.com/cjd102/g3/front/donation";
    };

    $order_id = "ORDER" . date("YmdHis");

    // 基礎參數
    $ecpayData = [
      "MerchantID" => $MerchantID,
      "MerchantTradeNo" => $order_id,
      "MerchantTradeDate" => date('Y/m/d H:i:s'),
      "PaymentType" => "aio",
      "TotalAmount" => $_POST["TotalAmount"],
      "TradeDesc" => $_POST["TradeDesc"],
      "ItemName" => $_POST["ItemName"],
      "CustomField1" => $_POST["CustomField1"], // member_id
      "CustomField2" => $_POST["CustomField2"], // once or monthly
      "ReturnURL" => $backurl . "/handle_return_url.php",
      "ChoosePayment" => "Credit", // 定期定額必須是 Credit
      "EncryptType" => 1,
      "IgnorePayment" => "WeiXin#TWQR#BNPL#CVS#BARCODE#ATM#WebATM",
      "ClientBackURL" => $fronturl
    ];

    // --- 定期定額判斷邏輯 ---
    if ($_POST["TradeDesc"] === "monthly") {
        $ecpayData["PeriodAmount"] = $_POST["TotalAmount"]; // 每期扣款金額
        $ecpayData["PeriodType"] = "M";                    // M: 每月扣款
        $ecpayData["Frequency"] = 1;                       // 頻率：1 (每1個月)
        $ecpayData["ExecTimes"] = 99;                      // 執行次數 (99為長期訂閱)
        // 定期定額建議設定此 URL 接收後續每期扣款結果
        $ecpayData["PeriodReturnURL"] = $backurl . "/handle_return_url.php";
    }

    $CheckMacValue = generateCheckMacValue($ecpayData);
  }
?>

<!DOCTYPE html>
<html lang="zh-Hant">
  <head>
    <meta charset="utf-8">
    <title>訂單付款</title>
  </head>
  <body>
    <?php
      if(isset($_POST["UseEcpay"]) && $_POST["UseEcpay"] == "ecpay"){
        echo '<form id="ecpayForm" method="post" action="https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5">';
        foreach ($ecpayData as $key => $value) {
            echo '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        echo '<input type="hidden" name="CheckMacValue" value="' . $CheckMacValue . '">';
        echo '</form>';
        echo '<script>document.getElementById("ecpayForm").submit();</script>';
      } else {
        echo "<p>異常</p>";
      }
    ?>
  </body>
</html>