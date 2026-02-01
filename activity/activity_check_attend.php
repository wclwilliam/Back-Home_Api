<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

$userId = $_GET['user_id'] ?? 0;
$activityId = $_GET['activity_id'] ?? 0;

if(!$userId || !$activityId) {
  echo json_encode(['status' => 'error', 'isParticipant' => 'false']);
  exit;
}

$checkSql = "
  SELECT ACTIVITY_SIGNUP_ID
  FROM ACTIVITY_SIGNUPS 
  WHERE ACTIVITY_ID = ? AND USER_ID = ? AND CANCEL = 0
";

$stmt = $pdo->prepare($checkSql);
$stmt->execute([ $activityId, $userId]);

echo json_encode([
  'status' => 'success',
  'isParticipant' => $stmt->rowCount() > 0
]);


?>