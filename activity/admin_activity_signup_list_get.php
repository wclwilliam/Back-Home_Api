<?php

require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    if(!isset($_GET['activity_id']) || empty($_GET['activity_id'])){
    throw new Exception("缺少活動ID參數");
    }
    $activityId = (int)$_GET['activity_id'];

    // 1. 基礎 SQL：撈出「對應」活動的所有資訊
    $sql = "
        SELECT * FROM ACTIVITY_SIGNUPS 
        WHERE ACTIVITY_ID = :aid
        ORDER BY CREATED_AT ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':aid', $activityId, PDO::PARAM_INT);
    $stmt->execute();

    $signups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $signups
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>