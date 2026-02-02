<?php
//載入基本設定
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
  //檢查請求方式
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        throw new Exception('請使用POST方式');
    }
    // 接收前端 POST 的 JSON 資料
    $input = json_decode(file_get_contents('php://input'), true);

    // 基本欄位檢查
    if ( 
        !isset($input['activity_id']) || 
        !isset($input['attendance_list']) 
    ) {
        echo json_encode(['status' => 'error', 'message' => '缺少必要欄位']);
        exit;
    }

    $activityId = (int)$input['activity_id'];
    $attendanceList = $input['attendance_list'];

    if(!is_array($attendanceList)){
        throw new Exception('出席名單錯誤');
    }
$sql = "UPDATE ACTIVITY_SIGNUPS 
            SET ATTENDED = :attended 
            WHERE USER_ID = :uid AND ACTIVITY_ID = :aid";
    $stmt = $pdo->prepare($sql);

    //  開始交易
    $pdo->beginTransaction();

    // 執行迴圈
    foreach($attendanceList as $item){
        $uid = $item['user_id'];
        $attended = $item['attended'];
        
        $stmt->execute([
            ':attended' => $attended,
            ':uid' => $uid,
            ':aid' => $activityId
        ]);
    }
    // 提交交易
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => '出席名單更新成功'
    ]);

} catch (PDOException $e) {
    if($pdo->inTransaction()){
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => '出席名單更新失敗: ' . $e->getMessage()
    ]);
}
?>