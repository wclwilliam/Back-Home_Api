<?php
//載入基本設定
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 接收前端 POST 的 JSON 資料
    $input = json_decode(file_get_contents('php://input'), true);

    // 基本欄位檢查
    if(
      !isset($input['user_id']) || 
      !isset($input['review_id']) || 
      !isset($input['reason'])
      ) {
        echo json_encode(['status' => 'error' , 'message' => '缺少必要參數']);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    $reviewId = (int)$input['review_id'];
    $reason = trim($input['reason']);
    
    if (empty($reason)) {
        echo json_encode(['status' => 'error', 'message' => '請填寫檢舉原因']);
        exit;
    }
    // 檢查是否檢舉過留言
    $checksql = "
      SELECT REPORT_ID
      FROM ACTIVITY_REVIEW_REPORTS
      WHERE REVIEW_ID = ? AND  USER_ID = ?
    ";
    $checkStmt = $pdo->prepare($checksql);
    $checkStmt->execute([$reviewId, $userId]);

    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => '您已經檢舉過這則留言了，我們會盡快處理。']);
        exit;
    }
  //寫入留言檢舉資料表
    $insertSql = "
    INSERT INTO ACTIVITY_REVIEW_REPORTS
    ( REVIEW_ID, USER_ID, REASON, REPORT_STATUS, CREATED_AT )
    VALUES ( ? , ? , ?, 'pending', NOW() )";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$reviewId , $userId, $reason]);

    echo json_encode([
        'status' => 'success',
        'message' => '檢舉已送出，感謝您的回報！'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>