<?php
//載入基本設定
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

function getAdminIdFromToken() {
    $authHeader = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $tokenStr = $matches[1];
        $parts = explode('.', $tokenStr);

        if(count($parts) === 3) {
            $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
            $payload = json_decode($payloadJson, true);

            return $payload['sub'] ?? null;

        }
    }
    return null;
}
try {
    //檢查請求方式
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        throw new Exception('請使用POST方式');
    }
    $adminId = getAdminIdFromToken();

    if (!$adminId) {
        throw new Exception("無法取得管理者身分，請重新登入");
    }
    
    //接收資料(包括封面圖片)
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $title = $_POST['title'] ?? '';
    $categoryVal = $_POST['category'] ?? ''; // 前端傳來 "淨灘"
    $region = $_POST['region'] ?? '';
    $location = $_POST['location'] ?? '';
    $description = $_POST['intro'] ?? '';
    $notes = $_POST['note'] ?? '';
    $maxPeople = empty($_POST['maxVolunteers']) ? 0 : (int)$_POST['maxVolunteers'];
    $statusVal = $_POST['status'] ?? '草稿';
    // 時間欄位 
    $actStart = $_POST['activityTime'][0] ?? '';
    $actEnd = $_POST['activityTime'][1] ?? '';
    // 報名截止時間 
    $signupEnd = $_POST['registrationTime'][1] ?? '';
    //資料轉換
    $catMap = [
        '淨灘' => 1,
        '巡守' => 2,
        '照護' => 3
    ];
    $catId = $catMap[$categoryVal] ?? 1;

    $statusMap = [
        '草稿' => 0,
        '發布' => 1,
        '取消' => 2,
    ];
    $statusCode = $statusMap[$statusVal] ?? 0;

    function fmtDate($date){
        if(empty($date)) return date('Y-m-d H:i:s');
        return date('Y-m-d H:i:s', strtotime($date));
    }

    $actStartStr = fmtDate($actStart);
    $actEndStr = fmtDate($actEnd);
    $signupEndStr = fmtDate($signupEnd);

    
    // 圖片處理
    $imagePath = null;
    if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/actCover/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExt = pathinfo($_FILES['imageFile']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('act_') . '.' . $fileExt;
        
        if (move_uploaded_file($_FILES['imageFile']['tmp_name'], $uploadDir . $newFileName)) {
            $imagePath = $newFileName;
        }
    }

    //判斷是insert or update
    $isInsert = empty($id) || $id === 'new' || $id === 'undefined';

    if($isInsert) {
        $sql = "INSERT INTO ACTIVITIES (
            ACTIVITY_TITLE, ACTIVITY_CATEGORY_ID, ACTIVITY_DESCRIPTION, ACTIVITY_NOTES,
            ACTIVITY_LOCATION, ACTIVITY_LOCATION_AREA, 
            ACTIVITY_START_DATETIME, ACTIVITY_END_DATETIME, ACTIVITY_SIGNUP_END_DATETIME,
            ACTIVITY_MAX_PEOPLE, ACTIVITY_STATUS, ACTIVITY_COVER_IMAGE, ADMIN_ID, ACTIVITY_CREATED_AT
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , NOW())";
        $params = [
            $title, $catId, $description, $notes, $location, $region,$actStartStr, $actEndStr, $signupEndStr, $maxPeople, $statusCode, $imagePath, $adminId
        ];
        $pdo->prepare($sql)->execute($params);
        $newId = $pdo->lastInsertId();
        $msg = "活動新增成功";


    } else {//update
        $sql = "UPDATE ACTIVITIES SET 
            ACTIVITY_TITLE=?, ACTIVITY_CATEGORY_ID=?, ACTIVITY_DESCRIPTION=?, ACTIVITY_NOTES=?,
            ACTIVITY_LOCATION=?, ACTIVITY_LOCATION_AREA=?, 
            ACTIVITY_START_DATETIME=?, ACTIVITY_END_DATETIME=?, ACTIVITY_SIGNUP_END_DATETIME=?,
            ACTIVITY_MAX_PEOPLE=?, ACTIVITY_STATUS=? ,
            ADMIN_ID=?, ACTIVITY_CREATED_AT=NOW()
        ";
        $params = [
            $title, $catId, $description, $notes, $location, $region,
            $actStartStr, $actEndStr, $signupEndStr, $maxPeople, $statusCode,
            $adminId
        ];

        // 如果有上傳新圖片，才加入 SQL 和參數
        if($imagePath) {
            $sql .= " , ACTIVITY_COVER_IMAGE = ? ";
            $params[] = $imagePath;
        }

        $sql .= " WHERE ACTIVITY_ID=?";
        $params[] = $id;

        $pdo->prepare($sql)->execute($params);
        $newId = $id;
        $msg = "活動更新成功";
    }


    echo json_encode([
        'status' => 'success',
        'message' => $msg,
        'id' => $newId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '儲存失敗: ' . $e->getMessage()
    ]);
}
?>