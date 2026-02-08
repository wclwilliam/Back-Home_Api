<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

// 1. 處理預檢請求 (OPTIONS)
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "缺少資料編號 (ID)"]);
        exit();
    }

    $id = intval($data['id']);

    try {
        // --- 步驟 A: 先查詢圖片路徑 ---
        $selectSql = "SELECT `FILE_PATH` FROM `FINANCIAL_REPORTS` WHERE `FINANCIAL_REPORT_ID` = :id";
        $selectStmt = $pdo->prepare($selectSql);
        $selectStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $selectStmt->execute();
        $report = $selectStmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "找不到該筆資料，無法刪除"]);
            exit();
        }

        $filePath = $report['FILE_PATH']; // 例如：reports/financial_report_2023.png

        // --- 步驟 B: 刪除實體檔案 ---
        // 這裡的路徑需根據你的專案結構調整，假設 reports 資料夾在 API 的上一層
        $fullPath = "../uploads/" . $filePath; 

        if (file_exists($fullPath)) {
            if (!unlink($fullPath)) {
                // 檔案刪除失敗的處理（可選，通常若檔案存在但刪不掉可能是權限問題）
                error_log("無法刪除實體檔案: " . $fullPath);
            }
        }

        // --- 步驟 C: 刪除資料庫紀錄 ---
        $deleteSql = "DELETE FROM `FINANCIAL_REPORTS` WHERE `FINANCIAL_REPORT_ID` = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $deleteStmt->execute();

        if ($deleteStmt->rowCount() > 0) {
            echo json_encode([
                "status" => "success", 
                "message" => "編號 $id 的資料及相關圖片已成功刪除"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "資料庫紀錄刪除失敗"]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "資料庫錯誤：" . $e->getMessage()]);
    }

    $pdo = null;
    exit();
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "不允許的方法"]);
?>