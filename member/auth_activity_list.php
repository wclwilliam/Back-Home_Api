<?php
/**
 * 龜途專案 - 前台：個人活動中心全功能整合 API
 * 功能：一次回傳時數加總、即將到來、歷史紀錄、已取消活動
 */

require_once __DIR__ . '/../common/cors.php'; // 處理 CORS 規範
require_once __DIR__ . '/../common/conn.php'; // 資料庫連線
require_once __DIR__ . '/auth_guard.php'; // 驗證會員登入狀態

header('Content-Type: application/json; charset=utf-8');

try {
    // 臨時測試用：直接使用 member_id = 2（正式環境請改回 requireAuth）
    $member_id = 2;
    // $member_id = requireAuth($pdo);
    
    $current_date = date('Y-m-d H:i:s');

    // SQL 撈取該會員所有報名紀錄（使用別名對應前端期望的字段名）
    $sql = "SELECT 
                a.ACTIVITY_ID,
                a.ACTIVITY_TITLE as TITLE,
                DATE(a.ACTIVITY_START_DATETIME) as START_DATE,
                TIME_FORMAT(a.ACTIVITY_START_DATETIME, '%H:%i') as START_TIME,
                TIME_FORMAT(a.ACTIVITY_END_DATETIME, '%H:%i') as END_TIME,
                a.ACTIVITY_LOCATION as LOCATION,
                s.ACTIVITY_SVC_HOURS as VOLUNTEER_HOURS,
                s.ATTENDED,   -- 1:已出席, 0:未出席
                s.CANCEL      -- 1:已取消, 0:正常
            FROM ACTIVITY_SIGNUPS s
            JOIN ACTIVITIES a ON s.ACTIVITY_ID = a.ACTIVITY_ID
            WHERE s.USER_ID = :member_id 
            ORDER BY a.ACTIVITY_START_DATETIME ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id]);
    $all_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 定義回傳結構
    $result = [
        "total_accumulated_hours" => 0, // 總累積時數
        "upcoming_events" => [],        // 未來即將參加
        "past_events" => [],            // 歷史參與紀錄
        "cancelled_events" => []        // 已取消紀錄
    ];

    foreach ($all_records as $row) {
        // 1. 優先分類「已取消」(CANCEL = 1)
        if ((int)$row['CANCEL'] === 1) {
            $result['cancelled_events'][] = $row;
            continue;
        }

        // 2. 根據日期判斷「未來」或「歷史」
        if ($row['START_DATE'] >= date('Y-m-d')) {
            $result['upcoming_events'][] = $row;
        } else {
            $result['past_events'][] = $row;
            
            // 3. 只有歷史活動且「已出席」才加總時數
            if ((int)$row['ATTENDED'] === 1) {
                $result['total_accumulated_hours'] += (float)$row['VOLUNTEER_HOURS'];
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $result
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "資料讀取失敗"]);
}