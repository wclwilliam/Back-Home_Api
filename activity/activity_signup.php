<?php
//載入基本設定
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 接收前端 POST 的 JSON 資料
    $input = json_decode(file_get_contents('php://input'), true);

    // 基本欄位檢查
    if (
        !isset($input['user_id']) || 
        !isset($input['activity_id']) || 
        !isset($input['name']) || 
        !isset($input['idNumber'])
    ) {
        throw new Exception("缺少必要欄位");
    }

    $userId = (int)$input['user_id'];
    $activityId = (int)$input['activity_id'];
    $sync = $input['sync'] ?? false;

    // --- 開始資料庫交易 (Transaction) ---
    // 確保接下來的一連串動作 (檢查、寫入、扣名額) 要嘛全部成功，要嘛全部失敗
    $pdo->beginTransaction();

    // 檢查是否重複報名
    // 查詢該會員是否已報名過此活動 (且未取消 CANCEL=0)
    $checkSql = "SELECT ACTIVITY_SIGNUP_ID FROM ACTIVITY_SIGNUPS WHERE USER_ID = ? AND ACTIVITY_ID = ? AND CANCEL = 0";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$userId, $activityId]);
    
    if ($checkStmt->rowCount() > 0) {
        throw new Exception("您已經報名過此活動囉！");
    }

    // 檢查活動狀態與名額 (使用 FOR UPDATE 鎖定這筆資料，防止多人同時搶名額)
    $actSql = "
        SELECT ACTIVITY_MAX_PEOPLE, ACTIVITY_SIGNUP_PEOPLE, ACTIVITY_SIGNUP_END_DATETIME, ACTIVITY_STATUS 
        FROM ACTIVITIES WHERE ACTIVITY_ID = ? FOR UPDATE";
    $actStmt = $pdo->prepare($actSql);
    $actStmt->execute([$activityId]);
    $activity = $actStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        throw new Exception("找不到此活動");
    }
    
    // 檢查是否發布中
    if ($activity['ACTIVITY_STATUS'] != 1) {
        throw new Exception("此活動目前暫停報名");
    }

    // 檢查是否超過報名截止時間
    if (new DateTime() > new DateTime($activity['ACTIVITY_SIGNUP_END_DATETIME'])) {
        throw new Exception("報名時間已截止");
    }

    // 檢查名額是否已滿
    if ($activity['ACTIVITY_SIGNUP_PEOPLE'] >= $activity['ACTIVITY_MAX_PEOPLE']) {
        throw new Exception("很抱歉，名額已滿");
    }

    // 寫入報名資料表 (ACTIVITY_SIGNUPS)
    $insertSql = "
        INSERT INTO ACTIVITY_SIGNUPS 
        (USER_ID, ACTIVITY_ID, ATTENDED, REAL_NAME, ID_NUMBER, PHONE, EMAIL, BIRTHDAY, EMERGENCY, EMERGENCY_TEL, CREATED_AT)
        VALUES 
        (:uid, :aid,:attend, :name, :idnum, :phone, :email, :bday, :emg, :emgTel, NOW())
    ";
    
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':uid' => $userId,
        ':aid' => $activityId,
        ':attend' => 1,
        ':name' => $input['name'],
        ':idnum' => $input['idNumber'],
        ':phone' => $input['phone'],
        ':email' => $input['email'],
        ':bday' => $input['birthday'],
        ':emg' => $input['emergencyName'],
        ':emgTel' => $input['emergencyPhone']
    ]);

    //勾選同步到會員資料
    if($sync) {
        $updateMemberSql = "
        UPDATE MEMBERS SET
            MEMBER_REALNAME = :name,
            ID_NUMBER = :idnum,
            MEMBER_PHONE = :phone,
            BIRTHDAY = :bday,
            EMERGENCY = :emg,
            EMERGENCY_TEL = :emgTel
            WHERE MEMBER_ID = :uid ";
        $memberSql = $pdo->prepare($updateMemberSql);
        $memberSql->execute([
            ':name' => $input['name'],
            ':idnum' => $input['idNumber'],
            ':phone' => $input['phone'],
            ':bday' => $input['birthday'],
            ':emg' => $input['emergencyName'],
            ':emgTel' => $input['emergencyPhone'],
            ':uid' => $userId
        ]);
    }

    // 更新活動主表的已報名人數 (ACTIVITY_SIGNUP_PEOPLE + 1)
    $updateSql = "UPDATE ACTIVITIES SET ACTIVITY_SIGNUP_PEOPLE = ACTIVITY_SIGNUP_PEOPLE + 1 WHERE ACTIVITY_ID = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$activityId]);

    // --- 提交交易 ---
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => '報名成功！期待您的參與'
    ]);

} catch (Exception $e) {
    // 若發生任何錯誤，回滾交易 (復原資料庫狀態)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>