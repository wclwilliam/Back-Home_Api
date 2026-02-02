<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

// 設定 Header
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    
    // 1. 檢查參數是否存在且不為空
    if (isset($_GET["member_id"]) && !empty($_GET["member_id"])) {
        $member_id = $_GET["member_id"]; 
    
        try {
            // 2. 執行查詢
            $sql = "SELECT * FROM subscription WHERE MEMBER_ID = :mid AND STATUS = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['mid' => $member_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // 3. 判斷是否有抓到資料
            if ($data) {
                // 有資料：回傳資料陣列
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                // 無資料：回傳成功但告知查無結果 (這不是報錯，是邏輯上的空值)
                echo json_encode([
                    "status" => "empty",
                    "message" => "該會員目前沒有進行中的訂閱",
                    "data" => []
                ]);
            }

        } catch (PDOException $e) {
            // 資料庫執行出錯
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "資料庫查詢失敗"
            ]);
        }
        
    } else {
        // 4. 參數缺失處理
        http_response_code(400); // Bad Request
        echo json_encode([
            "status" => "error",
            "message" => "缺少必要的參數: member_id"
        ]);
    }
} else {
    // 非 GET 請求處理
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "方法不允許"]);
}

exit();
?>