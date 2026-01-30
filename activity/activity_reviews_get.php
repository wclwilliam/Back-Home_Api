<?php

require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
  if(!isset($_GET['activity_id']) || empty($_GET['activity_id'])){
    echo json_encode([
        'status' => 'error', 
        'data' => []
        ]);
        exit;
  }
  $activity_id = (int)$_GET['activity_id'];
    // 撈出對應活動的成果
    $sql_results = "
        SELECT  R.REVIEW_ID,
                R.RATING,
                R.CONTENT,
                R.IS_VISIBLE,
                R.CREATED_AT,
                M.MEMBER_REALNAME AS USER_NAME,
                (
                SELECT COUNT(*) 
                FROM ACTIVITY_REVIEWS_LIKES              WHERE REVIEW_ID = R.REVIEW_ID
            ) AS LIKE_COUNT
        FROM ACTIVITY_REVIEWS AS R
        LEFT JOIN MEMBERS AS M ON R.USER_ID = M.MEMBER_ID
        WHERE R.ACTIVITY_ID = :aid
        ORDER BY CREATED_AT ASC
    ";

    $stmt = $pdo->prepare($sql_results);
    $stmt->execute([':aid' => $activity_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
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