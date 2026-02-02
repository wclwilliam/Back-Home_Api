<?php
// admin_activity_review_action.php

require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. 檢查請求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("請使用 POST 方法");
    }

    // 2. 接收 JSON 資料
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 檢查是否有指定動作
    $action = $input['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception("未指定操作動作 (action)");
    }

    // 3. 根據動作執行對應邏輯
    switch ($action) {
        
        // === 動作 A: 切換留言顯示狀態 (隱藏/顯示) ===
        case 'toggle_review':
            $reviewId = $input['review_id'] ?? 0;
            // 接收前端傳來的目標狀態 (1=顯示, 0=隱藏)
            // 若未傳送，預設為 1 (顯示)
            $isVisible = isset($input['is_visible']) ? (int)$input['is_visible'] : 1; 

            if ($reviewId === 0) {
                throw new Exception("缺少留言 ID");
            }

            $sql = "UPDATE ACTIVITY_REVIEWS SET IS_VISIBLE = ? WHERE REVIEW_ID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$isVisible, $reviewId]);

            $msg = $isVisible ? "留言已恢復顯示" : "留言已隱藏";
            echo json_encode(['status' => 'success', 'message' => $msg]);
            break;


        // === 動作 B: 更新檢舉處理狀態 (已處理/已駁回) ===
        case 'update_report':
            $reportId = $input['report_id'] ?? 0;
            $status = $input['status'] ?? ''; // e.g. '已處理', '已駁回'

            if ($reportId === 0 || empty($status)) {
                throw new Exception("缺少檢舉 ID 或狀態參數");
            }

            // 假設檢舉資料表名稱為 ACTIVITY_REVIEW_REPORTS
            // ★請確認您的資料庫表名是否正確★
            $sql = "UPDATE ACTIVITY_REVIEW_REPORTS SET REPORT_STATUS = ? WHERE REPORT_ID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status, $reportId]);

            echo json_encode(['status' => 'success', 'message' => "檢舉狀態已更新為：$status"]);
            break;

        default:
            throw new Exception("無效的操作動作");
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '操作失敗: ' . $e->getMessage()
    ]);
}
?>