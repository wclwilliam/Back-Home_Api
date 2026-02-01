<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. 檢查參數
    if (!isset($_GET['activity_id']) || empty($_GET['activity_id'])) {
        echo json_encode(['status' => 'error', 'message' => '缺少活動 ID']);
        exit;
    }

    $activityId = (int)$_GET['activity_id'];
    $data = [
        'metrics' => [],
        'photos' => []
    ];

    // 2. 查詢成果數據 (Metrics)
    // 修正了 SQL 變數名稱，並使用 fetchAll 抓取多筆
    $sql_metrics = "
        SELECT 
            R.METRIC_ID,
            R.VALUE,
            M.METRIC_NAME,
            M.METRIC_UNIT
        FROM ACTIVITY_RESULTS AS R
        LEFT JOIN RESULT_METRICS AS M ON R.METRIC_ID = M.METRIC_ID
        WHERE R.ACTIVITY_ID = :aid
    ";
    $stmt = $pdo->prepare($sql_metrics);
    $stmt->execute([':aid' => $activityId]);
    $data['metrics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. 查詢成果照片 (Photos)
    // 假設你未來會把照片存進 ACTIVITY_PHOTOS 表
    $sql_photos = "
        SELECT 
            PHOTO_ID,
            PHOTO_URL,
            DESCRIPTION
        FROM ACTIVITY_PHOTOS
        WHERE ACTIVITY_ID = :aid
    ";
    $stmt_photos = $pdo->prepare($sql_photos);
    $stmt_photos->execute([':aid' => $activityId]);
    $data['photos'] = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);

    // 4. 回傳整合資料
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>