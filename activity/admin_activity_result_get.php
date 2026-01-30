<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
  if(!isset($_GET['activity_id']) || empty($_GET['activity_id'])){
    throw new Exception("缺少活動ID參數");
  }
  $activityId = (int)$_GET['activity_id'];

  // 0. 先查詢該活動的 CATEGORY_ID (類別)，才知道要回傳哪些選單選項
  $sql_cat = "SELECT ACTIVITY_CATEGORY_ID FROM ACTIVITIES WHERE ACTIVITY_ID = ?";
  $stmt_cat = $pdo->prepare($sql_cat);
  $stmt_cat->execute([$activityId]);
  $actRow = $stmt_cat->fetch(PDO::FETCH_ASSOC);
  $categoryId = $actRow ? $actRow['ACTIVITY_CATEGORY_ID'] : 1;

  // ==========================================
  // 1. 取得「可選用的成果定義」 (給下拉選單用)
  // ==========================================
  // 撈取屬於該類別(例如淨灘=1) OR 通用類別(0) 的指標
  $sql_options = "
    SELECT METRIC_ID, METRIC_NAME, METRIC_UNIT 
    FROM RESULT_METRICS 
    WHERE ACTIVITY_CATEGORY_ID = :catId OR ACTIVITY_CATEGORY_ID = 0
  ";
  $stmt_opts = $pdo->prepare($sql_options);
  $stmt_opts->bindValue(':catId', $categoryId, PDO::PARAM_INT);
  $stmt_opts->execute();
  $metricOptions = $stmt_opts->fetchAll(PDO::FETCH_ASSOC);


  // ==========================================
  // 2. 取得「已儲存的成果數據」 (資料回填用)
  // ==========================================
  $sql_result = "
      SELECT 
        R.RESULT_ID,
        R.ACTIVITY_ID,
        R.METRIC_ID,
        R.VALUE,
        RM.METRIC_NAME,
        RM.METRIC_UNIT
      FROM ACTIVITY_RESULTS AS R 
      LEFT JOIN RESULT_METRICS AS RM ON R.METRIC_ID = RM.METRIC_ID
      WHERE R.ACTIVITY_ID = :aid
  ";
  $stmt = $pdo->prepare($sql_result);
  $stmt->bindValue(':aid', $activityId, PDO::PARAM_INT);
  $stmt->execute();
  $metricsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ==========================================
  // 3. 取得照片
  // ==========================================
  $sql_photos = "
    SELECT PHOTO_ID, ACTIVITY_ID, PHOTO_URL, CREATED_AT
    FROM ACTIVITY_PHOTOS
    WHERE ACTIVITY_ID = :aid
  ";
  $stmt_photos = $pdo->prepare($sql_photos);
  $stmt_photos->bindValue(':aid', $activityId, PDO::PARAM_INT);
  $stmt_photos->execute();
  $photoData = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
      'status' => 'success',
      'data' => [
        'options' => $metricOptions, // 新增：回傳下拉選單選項
        'metrics' => $metricsData,   // 已存數據
        'photos' => $photoData       // 照片
      ]
  ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>