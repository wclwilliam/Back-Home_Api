<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

// 確保回傳標頭為 JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // 取得前端傳來的 JSON 資料
    $input = json_decode(file_get_contents("php://input"), true);
    
    // 安全檢查：確保必要的參數都有傳過來
    if (!isset($input['subscriptionId']) || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "缺少必要參數"]);
        exit();
    }

    $subId = $input['subscriptionId'];
    $action = $input['action'];

    try {
        if ($action === 'cancel') {
            // 執行取消：改狀態、填入結束日期
            $sql = "UPDATE subscription SET STATUS = 0, END_DATE = CURDATE() WHERE SUBSCRIPTION_ID = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $subId]);
        } 
        elseif ($action === 'updateAmount') {
            // 執行修改金額
            if (!isset($input['amount'])) {
                throw new Exception("缺少金額參數");
            }
            $newAmount = $input['amount'];
            $sql = "UPDATE subscription SET AMOUNT = :amount WHERE SUBSCRIPTION_ID = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['amount' => $newAmount, 'id' => $subId]);
        }

        // 回傳成功訊息
        echo json_encode(["status" => "success", "action" => $action]);
        
    } catch (Exception $e) {
        // 如果資料庫執行出錯，回傳錯誤訊息給 Vue
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    
    $pdo = null;
    exit();
}

// 非 POST 請求的處理
http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
?>