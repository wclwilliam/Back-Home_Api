<?php
declare(strict_types=1);

require_once("../common/cors.php");
require_once("../common/config_loader.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 統一輸出 JSON 並結束
 */
function json_out(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 生成隨機 UUID（用於 state 和 nonce）
 */
function generate_uuid(): string
{
    return bin2hex(random_bytes(32)); // 64 字元
}

try {
    // 1️⃣ 檢查 LINE 設定
    $channelId = defined('LINE_CHANNEL_ID') ? trim((string)LINE_CHANNEL_ID) : '';
    $channelSecret = defined('LINE_CHANNEL_SECRET') ? trim((string)LINE_CHANNEL_SECRET) : '';
    
    if ($channelId === '' || $channelSecret === '' || 
        $channelId === 'YOUR_LINE_CHANNEL_ID' || 
        $channelId === '請到 LINE Developers Console 申請' ||
        $channelSecret === 'YOUR_LINE_CHANNEL_SECRET' ||
        $channelSecret === '請到 LINE Developers Console 申請') {
        json_out(500, [
            "error" => "LINE Login not configured",
            "message" => "Please set LINE_CHANNEL_ID and LINE_CHANNEL_SECRET in config.php"
        ]);
    }

    // 2️⃣ 生成 state 和 nonce（前端需保存 state 用於驗證）
    $state = generate_uuid();
    $nonce = generate_uuid();

    // 3️⃣ 組裝 LINE 授權 URL
    $lineAuthUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => LINE_CHANNEL_ID,
        'redirect_uri' => LINE_CALLBACK_URL,
        'state' => $state,
        'scope' => 'profile openid email',  // 請求權限：頭像、名稱、email
        'nonce' => $nonce,
    ]);

    // 4️⃣ 回傳授權 URL 給前端（前端需保存 state+nonce 用於驗證）
    json_out(200, [
        "status" => "success",
        "line_auth_url" => $lineAuthUrl,
        "state" => $state,
        "nonce" => $nonce,
        "expires_in" => 600,  // 10分鐘內有效（前端自行判斷）
    ]);

} catch (Throwable $e) {
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage(),
    ]);
}
