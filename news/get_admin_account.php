<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("../admin/auth_login.php");

// 處理 CORS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

try {
    // 驗證管理員身份，取得管理員資訊
    $admin = requireAdminAuth();
    
    // 回傳管理員帳號
    echo json_encode([
        "success" => true,
        "admin_account" => $admin['ADMIN_ID']  // 回傳 ADMIN_ID（帳號）
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "取得管理員資訊失敗: " . $e->getMessage()]);
}
?>