<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
  $userId = $_GET['user_id'] ?? 0;
  $activityId = $_GET['activity_id'] ?? 0;

  if(!$userId || !$activityId) {
    echo json_encode(['status' => 'error', 'isSignup' => 'false']);
    exit;
  }

  $checkSql = "
    SELECT count(*)
    FROM ACTIVITY_SIGNUPS 
    WHERE ACTIVITY_ID = ? AND USER_ID = ? AND CANCEL = 0
  ";

  $stmt = $pdo->prepare($checkSql);
  $stmt->execute([ $activityId, $userId]);
  $count = $stmt->fetchColumn();

  echo json_encode([
    'status' => 'success',
    'isSignup' => $count > 0 //已經報名過了
  ]);
}

catch (Exception $e){
  echo json_encode([
    'status' => 'error',
    'message' => $e->getMessage(),
    'isSignup' => false
  ]);
}


?>