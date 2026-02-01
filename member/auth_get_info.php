<?php
// 檔案位置：api/member/auth_get_info.php

// 1. 載入共用設定
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

try {
    // 2. 檢查是否有傳入 id
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['status' => 'error', 'message' => '缺少會員 ID']);
        exit;
    }

    $userId = (int)$_GET['id'];

    // 3. 撰寫 SQL
    // ★ 重點：使用 AS 別名，將資料庫欄位名稱 (左) 轉換為 前端需要的名稱 (右)
    // 這樣前端 ActivityInfoView.vue 裡的 user.name, user.email 才能讀到資料
    $sql = "SELECT 
                MEMBER_REALNAME AS name,
                MEMBER_EMAIL AS email,
                MEMBER_PHONE AS phone,
                ID_NUMBER AS idNumber,
                BIRTHDAY AS birthday,
                EMERGENCY AS emergencyName,
                EMERGENCY_TEL AS emergencyPhone
            FROM MEMBERS 
            WHERE MEMBER_ID = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($member) {
        echo json_encode([
            'status' => 'success',
            'data' => $member
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => '找不到會員資料'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => '資料庫錯誤: ' . $e->getMessage()
    ]);
}
?>