<?php

require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
  if(!isset($_GET['activity_id'])){
    throw new Exception("缺少活動ID參數");
  }
  $id = (int)$_GET['activity_id'];
    // 1. 基礎 SQL：撈出「全部」活動，不限制狀態
    $sql = "
        SELECT 
            A.*, 
            C.CATEGORY_VALUE 
        FROM ACTIVITIES AS A
        LEFT JOIN ACTIVITY_CATEGORIES AS C ON A.ACTIVITY_CATEGORY_ID = C.CATEGORY_ID
        WHERE A.ACTIVITY_ID = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        throw new Exception("找不到該活動資料");
    }

    echo json_encode([
        'status' => 'success',
        'data' => $activity
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>