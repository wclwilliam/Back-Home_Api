<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");

// 處理 CORS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// ===== 內聯函數定義 =====

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_verify($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header_b64, $payload_b64, $signature_b64) = $parts;
    
    // 驗證簽名
    $signing_input = $header_b64 . '.' . $payload_b64;
    $signature = base64url_decode($signature_b64);
    $expected_signature = hash_hmac('sha256', $signing_input, $secret, true);
    
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }
    
    // 解析 payload
    $payload = json_decode(base64url_decode($payload_b64), true);
    
    // 檢查過期時間
    if (isset($payload['exp']) && time() > $payload['exp']) {
        return false;
    }
    
    return $payload;
}

function requireAdminAuth() {
    global $pdo;
    
    // 從 Authorization header 取得 token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader)) {
        throw new Exception('未提供認證 token');
    }
    
    // 解析 Bearer token
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Token 格式錯誤');
    }
    
    $token = $matches[1];
    
    // 驗證並解析 JWT
    $payload = jwt_verify($token, JWT_SECRET);
    
    if (!$payload) {
        throw new Exception('Token 無效或已過期');
    }
    
    // 從資料庫獲取最新的管理員資訊
    $stmt = $pdo->prepare("
        SELECT ADMIN_ID, ADMIN_NAME, ADMIN_ROLE, ADMIN_ACTIVE
        FROM ADMIN_USER
        WHERE ADMIN_ID = :admin_id
        LIMIT 1
    ");
    $stmt->execute([':admin_id' => $payload['sub']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || (int)$admin['ADMIN_ACTIVE'] !== 1) {
        throw new Exception('管理員帳號不存在或已停用');
    }
    
    return $admin;
}

// ===== 主要邏輯 =====

try {
    // 驗證管理員身份，取得管理員資訊
    $admin = requireAdminAuth();
    
    // 回傳管理員帳號
    echo json_encode([
        "success" => true,
        "admin_account" => $admin['ADMIN_ID']
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "取得管理員資訊失敗: " . $e->getMessage()
    ]);
}
?>