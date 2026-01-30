<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

try {
    // 查詢目前最大的 NEWS_ID
    $sql = "SELECT MAX(NEWS_ID) as max_id FROM NEWS";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 計算下一個 ID
    $next_id = $result['max_id'] ? $result['max_id'] + 1 : 1;
    
    // 格式化為兩位數字串（如：01, 02, 03...）
    $formatted_id = str_pad($next_id, 2, '0', STR_PAD_LEFT);
    
    echo json_encode([
        "success" => true,
        "next_id" => $formatted_id,
        "numeric_id" => $next_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "資料庫查詢失敗: " . $e->getMessage()]);
}