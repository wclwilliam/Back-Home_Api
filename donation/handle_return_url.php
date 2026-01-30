<?php
  date_default_timezone_set("Asia/Taipei");

  // 因為這個邏輯不一樣所以資料庫自己引入
  /**
 * 資料庫連線設定
 * 自動偵測本地或遠端環境
 */

// ========== 錯誤處理設定 ==========
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== 時區設定 ==========
date_default_timezone_set("Asia/Taipei");

// ========== 環境偵測與資料庫設定 ========== 判斷是不是代理伺服器或本地
if (str_contains($_SERVER["HTTP_HOST"], "undelighted") || str_contains($_SERVER["HTTP_HOST"], "localhost")) {
  // localhost
  $db_host = '127.0.0.1';
  $db_port = 8889;
  $db_dbname = 'backhome_db';

  $db_user = 'root';
  $db_password = 'root';
} else {
  // remote
  $db_host = '127.0.0.1';               // 資料庫主機(ip)
  $db_port = 3306;                      // 資料庫 port number
  $db_dbname = 'tibamefe_cjd102g3'; // 資料庫名稱 (需更改)

  $db_user = 'tibamefe_since2021';      // 可連線資料庫的帳號
  $db_password = 'vwRBSb.j&K#E';        // 該帳號的密碼
}

// ========== 建立資料庫連線 ==========
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_dbname;charset=utf8mb4";

try {
  $pdo = new PDO($dsn, $db_user, $db_password);

  // PDO 屬性設定
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

  // 設定資料庫時區
  $pdo->exec("SET time_zone = '+08:00'");

  // 注意：不要在這裡 echo 任何東西，會破壞 JSON 輸出
  // 如果需要測試連線，請直接訪問這個檔案

} catch (PDOException $e) {
  // 錯誤處理
  header('Content-Type: application/json');
  http_response_code(500);
  echo json_encode([
    "error" => "資料庫連線錯誤",
    "message" => $e->getMessage()
  ]);
  exit();
}


  // require_once("../common/conn.php");
  // 檢查是否有 POST 資料
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once("./generate_mac_value.php");

    // // 將 ecpay 回傳的資料，放在純文字檔，方便看資料
    // $filename = __DIR__ . '/ecpay_data.txt'; // 純文字檔
    // $timestamp = date('Y-m-d H:i:s'); // 取得當前時間
    // $postData = print_r($_POST, true); // 將 $_POST 資料轉為字串格式
    // $content = "==== $timestamp ====\n$postData\n"; // 準備要寫入檔案的內容(包含時間)
    // file_put_contents($filename, $content, FILE_APPEND | LOCK_EX); // 將內容寫入檔案(放在最後面)
    // // 若確定不需要再看資料了，可移除上面五行

    // 檢查 MacValue
    $ecpayMacValue = $_POST["CheckMacValue"];
    unset($_POST["CheckMacValue"]);
    $MacValue = generateCheckMacValue($_POST);

    // 回應訊息
    if($MacValue == $ecpayMacValue){

      try {
        $pdo->beginTransaction();

        $member_id = $_POST["CustomField1"];  //會員id
        // 相容性抓取金額：如果沒有 TradeAmt 就抓 Amount
        $amount = isset($_POST["TradeAmt"]) ? $_POST["TradeAmt"] : (isset($_POST["Amount"]) ? $_POST["Amount"] : 0);
        // 抓取交易序號：ReturnURL 是 TradeNo，PeriodReturnURL 可能是 Gwsr (或也是 TradeNo，建議做相容)
        $trade_no = isset($_POST["TradeNo"]) ? $_POST["TradeNo"] : (isset($_POST["Gwsr"]) ? $_POST["Gwsr"] : '');
        $trade_desc = $_POST["CustomField2"]; // 'once' 或 'monthly'
        $order_id = $_POST["MerchantTradeNo"]; // 'once' 或 'monthly'
        $success_times = isset($_POST["TotalSuccessTimes"]) ? (int)$_POST["TotalSuccessTimes"] : 0;

        $subscription_id = null; // 預設為 null 定期定額編號
        $donation_type_name = ($trade_desc === 'monthly') ? '定期定額' : '單次捐款';

        if ($trade_desc === "monthly" && $success_times === 0) { //第一次定期定額(要建立定期定額資料)
          
        // 先建立定期定額主表資料
            // 欄位對照：MEMBER_ID, AMOUNT, START_DATE, STATUS (預設為 1)
            $sqlSub = "INSERT INTO subscription (MEMBER_ID, AMOUNT, START_DATE, STATUS, ORDER_ID) VALUES (?, ?, CURDATE(), 1 , ?)";
            $stmtSub = $pdo->prepare($sqlSub);
            $stmtSub->execute([$member_id, $amount, $order_id]);
            
            // 取得剛才自動生成的流水號 ID
            $subscription_id = $pdo->lastInsertId();
        }

        // 定期定額第二次以後 (TotalSuccessTimes > 1) 要去資料庫找subscription_id
        if ($success_times > 1) {
            // 根據會員 ID 找到該會員「狀態為 1 (啟用中)」的排程
            $sqlFindSub = "SELECT SUBSCRIPTION_ID FROM SUBSCRIPTION WHERE MEMBER_ID = ? AND STATUS = 1 ORDER BY SUBSCRIPTION_ID DESC LIMIT 1";
            $stmtFind = $pdo->prepare($sqlFindSub);
            $stmtFind->execute([$member_id]);
            $sub_row = $stmtFind->fetch(PDO::FETCH_ASSOC);
            $subscription_id = $sub_row ? $sub_row['SUBSCRIPTION_ID'] : null;
        }


        $sqlDonation = "INSERT INTO donations ( 
                            MEMBER_ID, 
                            AMOUNT,
                            DONATION_DATE,
                            SUBSCRIPTION_ID,
                            PAYMENT_METHOD,
                            DONATION_TYPE,
                            TRANSACTION_ID 
                        ) VALUES (?, ?, NOW(), ?, '信用卡', ?, ?)";
                        
        $stmtDonation = $pdo->prepare($sqlDonation);
        $stmtDonation->execute([
            $member_id,
            $amount,
            $subscription_id, // 如果是單次則為 null，定期則為剛取得的 ID
            $donation_type_name,
            $trade_no
        ]);

        // 提交所有變更
        $pdo->commit();
        echo "1|OK";

    } catch (PDOException $e) {
        // 發生錯誤，撤回所有已執行的 SQL
        $pdo->rollBack();
        file_put_contents($filename, "SQL Error: " . $e->getMessage() . "\n", FILE_APPEND);
        echo "0|Error";
    }
    }
    
  } else {
    echo "請使用 POST 方法提交資料。";
  }
?>