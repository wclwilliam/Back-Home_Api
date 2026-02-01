<?php
//載入基本設定
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 接收前端 POST 的 JSON 資料
    $input = json_decode(file_get_contents('php://input'), true);

    // 基本欄位檢查
    if(!isset($input['user_id']) || !isset($input['review_id'])) {
      echo json_encode(['status' => 'error' , 'message' => '缺少必要參數']);
      exit;
    }
    
    $userId = (int)$input['user_id'];
    $reviewId = (int)$input['review_id'];
    
    //開始資料庫交易
    $pdo->beginTransaction();

    // 檢查是否按讚過留言
    $checksql = "
      SELECT *
      FROM ACTIVITY_REVIEWS_LIKES
      WHERE REVIEW_ID = ? AND  USER_ID = ?
    ";
    $stmt = $pdo->prepare($checksql);
    $stmt->execute([$reviewId, $userId]);

    $isLiked = $stmt->rowCount() > 0;
    $action = '';

    if($isLiked) {

    // 留言按過就刪除 (ACTIVITY_REVIEWS_LIKES)
    $deleteSql = "
        DELETE FROM ACTIVITY_REVIEWS_LIKES 
        WHERE REVIEW_ID = ? AND USER_ID = ? ";
    
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$reviewId, $userId]);

    // 更新心得留言按讚數 -1 (LIKE_COUNT - 1)
    $updateSql = "UPDATE ACTIVITY_REVIEWS SET LIKE_COUNT = GREATEST(LIKE_COUNT - 1, 0) WHERE REVIEW_ID = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$reviewId]);

    $action = 'unliked';

  } else {
    //還沒按過執行按讚行為
    $insertSql = "
    INSERT INTO ACTIVITY_REVIEWS_LIKES 
    ( REVIEW_ID, USER_ID, CREATED_AT )
    VALUES ( ? , ? , NOW() )";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$reviewId , $userId]);

    //(LIKE_COUNT + 1)
    $updateSql = " UPDATE ACTIVITY_REVIEWS
    SET LIKE_COUNT = LIKE_COUNT + 1 
    WHERE REVIEW_ID = ? ";

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$reviewId]);

    $action = 'liked';

  }
    // --- 提交交易 ---
    $pdo->commit();

    $countSql = "
    SELECT LIKE_COUNT 
    FROM ACTIVITY_REVIEWS 
    WHERE REVIEW_ID = ?";
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$reviewId]);
    $newCount = $countStmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'action' => $action,//按讚or取消
        'new_count' => $newCount //最新數字
    ]);

} catch (Exception $e) {
    // 若發生任何錯誤，回滾交易 (復原資料庫狀態)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>