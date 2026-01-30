<?php

require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
  if(!isset($_GET['activity_id']) || empty($_GET['activity_id'])){
    throw new Exception("缺少活動ID參數");
  }
  $activityId = (int)$_GET['activity_id'];

    // 撈出對應活動的所有留言
    $sql_reviews = "
        SELECT  R.REVIEW_ID,
                R.USER_ID,
                R.ACTIVITY_ID,
                R.RATING,
                R.CONTENT,
                R.IS_VISIBLE,
                R.CREATED_AT,
                M.MEMBER_REALNAME AS USER_NAME,
                (
                SELECT COUNT(*) 
                FROM ACTIVITY_REVIEWS_LIKES WHERE REVIEW_ID = R.REVIEW_ID
            ) AS LIKE_COUNT
        FROM ACTIVITY_REVIEWS AS R
        LEFT JOIN MEMBERS AS M ON R.USER_ID = M.MEMBER_ID
        WHERE R.ACTIVITY_ID = :aid
        ORDER BY CREATED_AT ASC
    ";

    $stmt = $pdo->prepare($sql_reviews);
    $stmt->bindValue(':aid', $activityId, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 撈出對應留言的所有檢舉
    //收集所有review id
    $reviewIds = array_column($reviews, 'REVIEW_ID');
    //檢舉對照表
    $reportsMap = [];

    if(!empty($reviewIds)) {
      $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
    }

    $sql_reports = "
        SELECT  Rep.REPORT_ID,
                Rep.REVIEW_ID,
                Rep.USER_ID AS REPORTER_ID,
                Rep.REASON,
                Rep.REPORT_STATUS,
                Rep.CREATED_AT,
                M.MEMBER_REALNAME AS USER_NAME
        FROM ACTIVITY_REVIEW_REPORTS AS Rep
        LEFT JOIN MEMBERS AS M ON Rep.USER_ID = M.MEMBER_ID
        WHERE Rep.REVIEW_ID in ($placeholders)
        ORDER BY CREATED_AT ASC
    ";

    $stmt_reports = $pdo->prepare($sql_reports);
    $stmt_reports->execute($reviewIds);
    $allreports = $stmt_reports->fetchAll(PDO::FETCH_ASSOC);

    //依照review_id 分組
    foreach( $allreports as $rep) {
      $rId = $rep['REVIEW_ID'];
      if(!isset($reportsMap[$rId])) {
        $reportsMap[$rId] = [];
      }
      $reportsMap[$rId][] = $rep;
    }

    // 組合資料 (Nested Structure)
    
    // 將檢舉陣列塞回對應的留言物件中
    foreach ($reviews as &$review) {
        $rId = $review['REVIEW_ID'];
        // 如果該留言有檢舉紀錄，就填入；否則給空陣列
        $review['reports'] = isset($reportsMap[$rId]) ? $reportsMap[$rId] : [];
        
        // 前端 ActivityComments 需要 reportCount 欄位
        $review['reportCount'] = count($review['reports']); 
    }


    echo json_encode([
        'status' => 'success',
        'data' => $reviews
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>