<?php
  date_default_timezone_set("Asia/Taipei");

  if(isset($_POST["UseEcpay"]) && $_POST["UseEcpay"] == "ecpay"){
    require_once("../common/conn.php");
    require_once("./generate_mac_value.php");

    // ======================================================= //
    $order_id = "test" . date("YmdHis");

    $ecpayData = [
      "MerchantID" => $MerchantID,
      "MerchantTradeNo" => $order_id,
      "MerchantTradeDate" => date('Y/m/d H:i:s'),
      "PaymentType" => "aio",
      "TotalAmount" => $_POST["TotalAmount"],
      "TradeDesc" => $_POST["TradeDesc"],
      "ItemName" => $_POST["ItemName"],
      "ReturnURL" => "https://undelighted-unadhesively-elenor.ngrok-free.dev/API/donation/handle_return_url.php",
      "ChoosePayment" => "Credit",
      "EncryptType" => 1,
      "IgnorePayment" => "WeiXin#TWQR#BNPL#CVS#BARCODE#ATM#WebATM",
      "ClientBackURL" => "https://tibamef2e.com/cjd102/g3/front/donation" // 在綠界平台，付款完成後，會出現「返回商店」按鈕，按下去後，單純頁面做轉向
    ];

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
        echo <<< HTML
          <form id="ecpayForm" method="post" action="https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5">
            <input type="hidden" name="MerchantID" value="{$ecpayData['MerchantID']}">
            <input type="hidden" name="MerchantTradeNo" value="{$ecpayData['MerchantTradeNo']}">
            <input type="hidden" name="MerchantTradeDate" value="{$ecpayData['MerchantTradeDate']}">
            <input type="hidden" name="PaymentType" value="{$ecpayData['PaymentType']}">
            <input type="hidden" name="TotalAmount" value="{$ecpayData['TotalAmount']}">
            <input type="hidden" name="TradeDesc" value="{$ecpayData['TradeDesc']}">
            <input type="hidden" name="ItemName" value="{$ecpayData['ItemName']}">
            <input type="hidden" name="ReturnURL" value="{$ecpayData['ReturnURL']}">
            <input type="hidden" name="ChoosePayment" value="{$ecpayData['ChoosePayment']}">
            <input type="hidden" name="EncryptType" value="{$ecpayData['EncryptType']}">
            <input type="hidden" name="IgnorePayment" value="{$ecpayData['IgnorePayment']}">
            <input type="hidden" name="ClientBackURL" value="{$ecpayData['ClientBackURL']}">
            <input type="hidden" name="CheckMacValue" value="{$CheckMacValue}">
          </form>
        HTML;
        echo '<script>document.getElementById("ecpayForm").submit();</script>';
      }else{
        echo "<p>異常</p>";
      }
    ?>
  </body>
</html>