<?php
//載入基本設定
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 接收前端 POST 的 JSON 資料
    $input = json_decode(file_get_contents('php://input'), true);

    // 基本欄位檢查
    if (
        !isset($input['user_id']) || 
        !isset($input['activity_id']) || 
        !isset($input['rating']) || 
        !isset($input['content'])
    ) {
        echo json_encode(['status' => 'error', 'message' => '缺少必要欄位']);
        exit;
    }

    $userId = (int)$input['user_id'];
    $activityId = (int)$input['activity_id'];
    $rating = (int)$input['rating'];
    $content = trim($input['content']);

    // 1. 檢查是否有報名此活動(且未取消 CANCEL=0)
    $checkSignupSql = "
      SELECT ACTIVITY_SIGNUP_ID 
      FROM ACTIVITY_SIGNUPS 
      WHERE USER_ID = ? AND ACTIVITY_ID = ? AND CANCEL = 0";
    $checkStmt = $pdo->prepare($checkSignupSql);
    $checkStmt->execute([$userId, $activityId]);
    
    // ★ 修正1：使用 $checkStmt 檢查，並加上 exit
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => '未參與此活動，無法留言']);
        exit;
    }

    // 2. 檢查是否已經留言過了
    $checkReviewSql = "
      SELECT REVIEW_ID
      FROM ACTIVITY_REVIEWS
      WHERE USER_ID = ? AND ACTIVITY_ID = ?"; // ★ 修正2：補上缺少的結束引號
      
    // ★ 修正3：變數名稱改為 $checkReviewSql
    $stmtReview = $pdo->prepare($checkReviewSql); 
    $stmtReview->execute([ $userId, $activityId ]);

    if ($stmtReview->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => '您已經發表過心得了喔！']);
        exit;
    }
        
    // 3. 寫入留言資料表 (ACTIVITY_REVIEWS)
    $insertSql = "
        INSERT INTO ACTIVITY_REVIEWS 
        (USER_ID, ACTIVITY_ID, RATING, CONTENT, IS_VISIBLE, LIKE_COUNT, CREATED_AT)
        VALUES 
        (:uid, :aid, :rating, :content, 1, 0, NOW())
    "; // ★ 修正4：移除 NOW() 前面多餘的逗號
    
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':uid' => $userId,
        ':aid' => $activityId,
        ':rating' => $rating,
        ':content' => $content
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => '感謝您的分享！心得已送出'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '資料庫錯誤: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>