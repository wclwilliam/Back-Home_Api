<?php
/**
 * 龜途專案 - 會員志工紀錄與時數加總 API
 * 用途：後台會員詳情頁面 (MembersDetailView.vue)
 */

// 1. 處理 CORS 與基礎引入
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/conn.php';

// 設置正確的 Content-Type
header('Content-Type: application/json; charset=UTF-8');

// 2. 接收參數
$member_id = $_GET['member_id'] ?? $_GET['id'] ?? 0;

if ($member_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid Member ID",
        "debug_received" => $_GET // 方便你在控制台看到收到了什麼
    ]);
    exit;
}

try {
    /**
     * 3. 執行 SQL 查詢 (與組員的活動表連動)
     * 邏輯：
     * - JOIN ACTIVITY_SIGNUPS (報名表) 與 activities (活動表)
     * - 條件：必須是該會員且「已出席」(ATTENDANCE_STATUS = 1)
     */
    
$sql = "SELECT 
            a.ACTIVITY_TITLE, 
            a.ACTIVITY_START_DATETIME AS ACTIVITY_DATE, 
            ar.ACTIVITY_SVC_HOURS AS ACTIVITY_HOURS
        FROM ACTIVITY_SIGNUPS ar
        JOIN ACTIVITIES a ON ar.ACTIVITY_ID = a.ACTIVITY_ID
        WHERE ar.USER_ID = :member_id 
        AND ar.ATTENDED = 1 
        ORDER BY a.ACTIVITY_START_DATETIME DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * 4. 計算累積總時數
     */
    $total_hours = 0;
    foreach ($history as $row) {
        $total_hours += (float)$row['ACTIVITY_HOURS'];
    }

    // 5. 回傳 JSON 格式 (對齊 Vue 檔中的變數名稱)
    echo json_encode([
        "status" => "success",
        "total_accumulated_hours" => $total_hours, // 對應 res.total_accumulated_hours
        "activity_history" => $history,           // 對應 res.activity_history
        "member_id" => $member_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "資料庫連線失敗: " . $e->getMessage()
    ]);
}