<?php

declare(strict_types=1);

require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
 * JWT（HS256）工具：不使用第三方套件
 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * 產生 JWT（HS256）
 * - 這裡沿用你們 login 的寫法
 */
function jwt_sign_hs256(array $payload, string $secret, string $typ = 'JWT'): string
{
    $header = ['alg' => 'HS256', 'typ' => $typ];

    $h = base64url_encode(json_encode($header, JSON_UNESCAPED_UNICODE));
    $p = base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));

    $sig = hash_hmac('sha256', $h . '.' . $p, $secret, true);
    $s = base64url_encode($sig);

    return $h . '.' . $p . '.' . $s;
}

/**
 * 1️⃣ 讀取 JSON body
 */
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    json_out(400, ["error" => "Invalid JSON body"]);
}

$email = isset($body['email']) ? trim((string)$body['email']) : '';

/**
 * 2️⃣ 基本驗證
 */
if ($email === '') {
    json_out(400, ["error" => "email is required"]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(400, ["error" => "invalid email format"]);
}

/**
 * 3️⃣ 檢查 JWT_SECRET 是否有設定（來自 common/config.php）
 */
if (!defined('JWT_SECRET') || trim((string)JWT_SECRET) === '') {
    json_out(500, ["error" => "server_misconfigured"]);
}

/**
 * 對外統一回覆（避免探測 email 是否存在）
 */
$public_ok = [
    "status" => "success",
    "message" => "若此 Email 存在，我們已寄出重設密碼連結。",
];

try {
    /**
     * 4️⃣ 查會員
     * ⚠️ 依你們 login/register：MEMBERS / MEMBER_EMAIL / MEMBER_ID / MEMBER_ACTIVE
     */
    $sql = "
        SELECT MEMBER_ID, MEMBER_EMAIL, MEMBER_ACTIVE
        FROM MEMBERS
        WHERE MEMBER_EMAIL = :email
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":email" => $email]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    // email 不存在：也回 success（防帳號枚舉）
    if (!$member) {
        json_out(200, $public_ok);
    }

    // 不啟用：建議也回 success，避免被探測帳號狀態
    if ((int)$member['MEMBER_ACTIVE'] !== 1) {
        json_out(200, $public_ok);
    }

    /**
     * 5️⃣ 產生重設密碼 token（JWT 格式，但 typ=RESET）
     * - exp 建議短：30分鐘
     */
    $now = time();
    $expSeconds = defined('RESET_PWD_EXP_SECONDS') ? (int)RESET_PWD_EXP_SECONDS : 30 * 60;

    $payload = [
        'iss' => defined('JWT_ISS_MEMBER') ? (string)JWT_ISS_MEMBER : 'backhome-member',
        'sub' => (int)$member['MEMBER_ID'],
        'email' => (string)$member['MEMBER_EMAIL'],
        'iat' => $now,
        'exp' => $now + $expSeconds,
        'purpose' => 'reset_password',
        'nonce' => base64url_encode(random_bytes(16)),
    ];

    $token = jwt_sign_hs256($payload, (string)JWT_SECRET, 'RESET');

    /**
     * 6️⃣ 組 reset URL（前端頁）
     */
    $frontendBase = defined('FRONTEND_BASE_URL') ? (string)FRONTEND_BASE_URL : (getenv('FRONTEND_BASE_URL') ?: 'http://localhost:5173');
    $resetUrl = rtrim($frontendBase, '/') . '/reset-password?token=' . urlencode($token);

    /**
     * 7️⃣ 寄信（或先 mock）
     * - dev: 回傳 resetUrl 方便 Postman 測
     * - prod: mail()
     */
    $appEnv = defined('APP_ENV') ? (string)APP_ENV : (getenv('APP_ENV') ?: 'dev');

    if ($appEnv === 'dev') {
        json_out(200, [
            ...$public_ok,
            "debug" => [
                "reset_url" => $resetUrl,
                "expires_in_seconds" => $expSeconds,
            ],
        ]);
    }

    // 使用 Brevo API 寄送重設密碼連結
    $apiKey = defined('BREVO_API_KEY') ? (string)BREVO_API_KEY : '';
    $baseUrl = defined('BREVO_BASE_URL') ? rtrim((string)BREVO_BASE_URL, '/') : 'https://api.brevo.com';

    if ($apiKey === '' || $apiKey === 'CHANGE_ME') {
        json_out(500, ["error" => "server_error", "message" => "brevo_api_key_missing"]);
    }

    $minutes = (int)ceil($expSeconds / 60);
    $htmlContent = "<p>你好：</p><p>請點擊以下連結重設密碼（連結有效約 {$minutes} 分鐘）：</p><p><a href='{$resetUrl}'>{$resetUrl}</a></p><p>若你沒有申請重設密碼，請忽略此信。</p>";
    $textContent = "你好：\n\n請點擊以下連結重設密碼（連結有效約 {$minutes} 分鐘）：\n{$resetUrl}\n\n若你沒有申請重設密碼，請忽略此信。\n";

    $payload = [
        'sender' => [
            'email' => defined('BREVO_FROM_EMAIL') ? BREVO_FROM_EMAIL : 'no-reply@example.com',
            'name' => defined('BREVO_FROM_NAME') ? BREVO_FROM_NAME : 'BackHome',
        ],
        'to' => [
            [ 'email' => $email ]
        ],
        'subject' => '重設密碼連結',
        'htmlContent' => $htmlContent,
        'textContent' => $textContent
    ];

    if (!function_exists('curl_init')) {
        json_out(500, ["error" => "server_error", "message" => "curl_not_enabled"]);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    if (defined('BREVO_CA_BUNDLE') && BREVO_CA_BUNDLE !== '') {
        curl_setopt($ch, CURLOPT_CAINFO, BREVO_CA_BUNDLE);
    }

    $resp = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        json_out(500, ["error" => "server_error", "message" => "curl_failed", "detail" => $curlErr]);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        json_out(500, ["error" => "server_error", "message" => "email_send_failed", "detail" => $resp]);
    }

    json_out(200, $public_ok);
} catch (Throwable $e) {
    json_out(500, [
        "error" => "server_error",
        "message" => $e->getMessage(),
    ]);
}
