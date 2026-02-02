<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 查詢前 10 名志工 (時數由高到低)
    // 條件：已出席 (ATTENDED=1) 且 未取消 (CANCEL=0)
    $sql = "
        SELECT 
            M.MEMBER_ID,
            M.MEMBER_REALNAME,
            SUM(S.ACTIVITY_SVC_HOURS) AS TOTAL_HOURS
        FROM ACTIVITY_SIGNUPS AS S
        JOIN MEMBERS AS M ON S.USER_ID = M.MEMBER_ID
        WHERE S.ATTENDED = 1 
          AND S.CANCEL = 0
        GROUP BY S.USER_ID
        ORDER BY TOTAL_HOURS DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 姓名遮罩處理 (保護隱私)
    foreach ($leaders as &$leader) {
        $name = $leader['MEMBER_REALNAME'];
        $len = mb_strlen($name, 'UTF-8');
        
        if ($len >= 2) {
             // 取第一個字
             $first = mb_substr($name, 0, 1, 'UTF-8');
             // 取最後一個字 (如果是 2 個字的名字，最後一個字也是第 2 個字)
             $last = mb_substr($name, -1, 1, 'UTF-8');
             
             if ($len == 2) {
                 // 兩個字：王明 -> 王O
                 $leader['DISPLAY_NAME'] = $first . 'O';
             } else {
                 // 三個字以上：王小明 -> 王O明
                 $leader['DISPLAY_NAME'] = $first . 'O' . $last;
             }
        } else {
            $leader['DISPLAY_NAME'] = $name;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $leaders
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>