<?php
  $MerchantID = "3002607";
  
  function generateCheckMacValue($ecpayData){
    $HashKey = "pwFHCqoQZGmho4w6";
    $HashIV = "EkRm7iFT261dpevs";

    ksort($ecpayData);
    $CheckMacValue = urldecode(http_build_query($ecpayData));
    $CheckMacValue = "HashKey=$HashKey&$CheckMacValue&HashIV=$HashIV";
    $CheckMacValue = urlencode($CheckMacValue);
    $CheckMacValue = str_replace("%2d", "-", $CheckMacValue);
    $CheckMacValue = str_replace("%5f", "_", $CheckMacValue);
    $CheckMacValue = str_replace("%2e", ".", $CheckMacValue);
    $CheckMacValue = str_replace("%21", "!", $CheckMacValue);
    $CheckMacValue = str_replace("%2a", "*", $CheckMacValue);
    $CheckMacValue = str_replace("%28", "(", $CheckMacValue);
    $CheckMacValue = str_replace("%29", ")", $CheckMacValue);
    $CheckMacValue = str_replace("%20", "+", $CheckMacValue);
    $CheckMacValue = strtolower($CheckMacValue);
    $CheckMacValue = hash("sha256", $CheckMacValue);
    $CheckMacValue = strtoupper($CheckMacValue);
    return $CheckMacValue;
  }
?>