<?php
header('Content-Type: application/json');
require_once '../common/conn.php'; 
require_once '../common/cors.php'; 

$member_id = $_GET['member_id'] ?? $_GET['id'] ?? 0;

if ($member_id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID 錯誤，收到的是: " . json_encode($_GET)]);
    exit;
}

// 根據 SQL 結構：使用 a.TITLE (活動名稱), s.USER_ID (報名關連)
$sql = "SELECT 
            a.TITLE as ACTIVITY_NAME, 
            a.ACTIVITY_DATE, 
            a.ACTIVITY_HOURS, 
            s.SIGNUP_STATUS, 
            s.ATTENDANCE_STATUS
        FROM activity_signups s
        JOIN activities a ON s.ACTIVITY_ID = a.ACTIVITY_ID
        WHERE s.USER_ID = ? AND s.ATTENDANCE_STATUS = 'attended'
        ORDER BY a.ACTIVITY_DATE DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$member_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 計算該會員總時數
    $total_hours = array_sum(array_column($activities, 'ACTIVITY_HOURS'));

    echo json_encode([
        "status" => "success",
        "total_accumulated_hours" => (int)$total_hours,
        "activity_history" => $activities
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>