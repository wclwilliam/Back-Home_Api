<?php
// admin_activity_result_save.php

require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("請使用 POST 方法");
    }

    // 1. 接收參數
    $activityId = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;
    if ($activityId === 0) {
        throw new Exception("缺少活動 ID");
    }

    // 接收 JSON 字串並解碼
    $resultsJson = $_POST['results'] ?? '[]';
    $deletedPhotosJson = $_POST['deleted_photos'] ?? '[]';
    
    $results = json_decode($resultsJson, true); // 成果列表 [{metric_id: 1, value: 10}, ...]
    $deletedPhotos = json_decode($deletedPhotosJson, true); // 要刪除的照片 ID 列表 [101, 102]

    // 開始交易
    $pdo->beginTransaction();

    // ==========================================
    // 2. 處理成果數值 (Metrics)
    // ==========================================
    $sql_del_metrics = "DELETE FROM ACTIVITY_RESULTS WHERE ACTIVITY_ID = ?";
    $stmt_del = $pdo->prepare($sql_del_metrics);
    $stmt_del->execute([$activityId]);

    if (!empty($results) && is_array($results)) {
        $sql_ins_metric = "INSERT INTO ACTIVITY_RESULTS (ACTIVITY_ID, METRIC_ID, VALUE, CREATED_AT) VALUES (?, ?, ?, NOW())";
        $stmt_ins = $pdo->prepare($sql_ins_metric);

        foreach ($results as $item) {
            // 確保 metric_id 和 value 都有值
            if (!empty($item['metric_id']) && isset($item['value'])) {
                $stmt_ins->execute([
                    $activityId,
                    $item['metric_id'],
                    $item['value']
                ]);
            }
        }
    }

    // ==========================================
    // 3. 處理照片刪除 (Deleted Photos)
    // ==========================================
    if (!empty($deletedPhotos) && is_array($deletedPhotos)) {
        // 先撈出檔名以便刪除實體檔案
        $placeholders = implode(',', array_fill(0, count($deletedPhotos), '?'));
        $sql_get_files = "SELECT PHOTO_URL FROM ACTIVITY_PHOTOS WHERE PHOTO_ID IN ($placeholders) AND ACTIVITY_ID = ?";
        
        // 參數：刪除 ID 列表 + 活動 ID (多一層檢查比較安全)
        $params = array_merge($deletedPhotos, [$activityId]);
        
        $stmt_get = $pdo->prepare($sql_get_files);
        $stmt_get->execute($params);
        $filesToDelete = $stmt_get->fetchAll(PDO::FETCH_COLUMN);

        // 刪除實體檔案
        $uploadDir = '../uploads/actResult/';
        foreach ($filesToDelete as $filename) {
            $filePath = $uploadDir . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // 刪除資料庫紀錄
        $sql_del_photos = "DELETE FROM ACTIVITY_PHOTOS WHERE PHOTO_ID IN ($placeholders) AND ACTIVITY_ID = ?";
        $stmt_del_p = $pdo->prepare($sql_del_photos);
        $stmt_del_p->execute($params);
    }

    // ==========================================
    // 4. 處理照片新增 (New Photos)
    // ==========================================
    // 前端 FormData 使用 'new_photos[]' 傳送多個檔案
    if (isset($_FILES['new_photos'])) {
        $uploadDir = '../uploads/actResult/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $sql_ins_photo = "INSERT INTO ACTIVITY_PHOTOS (ACTIVITY_ID, PHOTO_URL, CREATED_AT) VALUES (?, ?, NOW())";
        $stmt_ins_p = $pdo->prepare($sql_ins_photo);

        // 處理多檔上傳的迴圈結構
        $files = $_FILES['new_photos'];
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $newFileName = uniqid('res_') . '.' . $ext;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                    $stmt_ins_p->execute([$activityId, $newFileName]);
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => '成果儲存成功'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => '儲存失敗: ' . $e->getMessage()
    ]);
}
?>