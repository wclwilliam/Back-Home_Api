<?php

require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. 基礎 SQL：撈出「全部」活動，不限制狀態
    $sql = "
        SELECT 
            A.*, 
            C.CATEGORY_VALUE 
        FROM ACTIVITIES AS A
        LEFT JOIN ACTIVITY_CATEGORIES AS C ON A.ACTIVITY_CATEGORY_ID = C.CATEGORY_ID
    ";

    // status篩選功能
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $sql .= " WHERE A.ACTIVITY_STATUS = " . (int)$_GET['status'];
    }

    // 依照「建立時間」排序 (新的在上面)
    $sql .= " ORDER BY A.ACTIVITY_ID DESC"; 

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $activities
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>