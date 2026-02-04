<?php
  $MerchantID = "3002607";

  function generateCheckMacValue($ecpayData){

    $HashKey = "pwFHCqoQZGmho4w6";
    $HashIV = "EkRm7iFT261dpevs";

    // 步驟 1：將陣列欄位依照字母順序 (A-Z) 排序
    // 綠界規定：所有參數必須按欄位名稱升冪排序
    ksort($ecpayData);

    // 步驟 2：將陣列轉換為 URL 查詢字串 (例如 ItemName=Apple&MerchantID=3002607...)
    // urldecode 是為了防止原始字串先被進行一次編碼導致加密錯誤
    // http_build_query()會把中文等的數據轉為百分比編碼（%xx）
    // 所以要用urldecode()變回來
    $CheckMacValue = urldecode(http_build_query($ecpayData));

    // 步驟 3：在字串前後加上 HashKey 與 HashIV
    $CheckMacValue = "HashKey=$HashKey&$CheckMacValue&HashIV=$HashIV";

    // 步驟 4：進行整串字串的 URL 編碼 (Url Encode)
    $CheckMacValue = urlencode($CheckMacValue);

    // 步驟 5：轉換為小寫 (綠界規定：在進行符號替換前通常先轉小寫，或是加密前轉小寫)
    $CheckMacValue = strtolower($CheckMacValue);

    /**
     * 步驟 6：符號替換 (關鍵步驟)
     * 綠界使用的 URL 編碼與 PHP 預設的 urlencode 有細微差異。
     * 必須將特定的編碼符號轉回原始字元，否則 Hash 值會與綠界計算的不符。
     */
    $CheckMacValue = str_replace("%2d", "-", $CheckMacValue);
    $CheckMacValue = str_replace("%5f", "_", $CheckMacValue);
    $CheckMacValue = str_replace("%2e", ".", $CheckMacValue);
    $CheckMacValue = str_replace("%21", "!", $CheckMacValue);
    $CheckMacValue = str_replace("%2a", "*", $CheckMacValue);
    $CheckMacValue = str_replace("%28", "(", $CheckMacValue);
    $CheckMacValue = str_replace("%29", ")", $CheckMacValue);
    
    // 綠界規定：空格編碼後的 %20 必須置換成 + 號
    $CheckMacValue = str_replace("%20", "+", $CheckMacValue);

    // 步驟 7：使用 SHA256 演算法進行雜湊加密
    $CheckMacValue = hash("sha256", $CheckMacValue);

    // 步驟 8：將加密後的字串轉為大寫，這就是最終的 CheckMacValue
    $CheckMacValue = strtoupper($CheckMacValue);

    return $CheckMacValue;
  }
?>