<?php
/**
 * config.example.php
 * 範本設定檔
 */

// ===== JWT 設定 =====
$jwtSecret = getenv('JWT_SECRET');
if ($jwtSecret === false || trim((string)$jwtSecret) === '') {
    $jwtSecret = 'CHANGE_ME';
}
define('JWT_SECRET', $jwtSecret);
define('JWT_ISS_ADMIN', 'backhome-admin');
define('JWT_ISS_MEMBER', 'backhome-member');
define('JWT_EXP_SECONDS_ADMIN', 60 * 60 * 6);  // 管理員：6 小時
define('JWT_EXP_SECONDS_MEMBER', 60 * 60 * 24 * 7);  // 會員：7 天

// ===== 驗證碼設定 =====
define('VERIFICATION_CODE_EXPIRE_MINUTES', 10);  // 驗證碼有效期限（分鐘）

// ===== Brevo 設定 =====
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: 'CHANGE_ME');
define('BREVO_FROM_EMAIL', getenv('BREVO_FROM_EMAIL') ?: 'no-reply@example.com');
define('BREVO_FROM_NAME', getenv('BREVO_FROM_NAME') ?: 'BackHome');
define('BREVO_BASE_URL', getenv('BREVO_BASE_URL') ?: 'https://api.brevo.com');
define('BREVO_CA_BUNDLE', getenv('BREVO_CA_BUNDLE') ?: __DIR__ . '/cacert.pem'); // SSL CA 憑證路徑

// ===== LINE Login 設定 =====
define('LINE_CHANNEL_ID', getenv('LINE_CHANNEL_ID') ?: 'YOUR_LINE_CHANNEL_ID');
define('LINE_CHANNEL_SECRET', getenv('LINE_CHANNEL_SECRET') ?: 'YOUR_LINE_CHANNEL_SECRET');
define('LINE_CALLBACK_URL_DEV', 'http://localhost:5173/');  // 本地開發
define('LINE_CALLBACK_URL_PROD', 'https://yourdomain.com/front/');  // 正式環境
// 自動偵測環境（根據 HTTP_HOST 判斷）
$isLocalhost = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
define('LINE_CALLBACK_URL', $isLocalhost ? LINE_CALLBACK_URL_DEV : LINE_CALLBACK_URL_PROD);
define('LINE_STATE_EXPIRE_MINUTES', 10);  // state 有效期限（分鐘）

// ===== 忘記密碼設定 =====
define('RESET_PWD_EXP_SECONDS', 30 * 60);  // 重設密碼 token 有效期限（30 分鐘）
define('FRONTEND_BASE_URL', $isLocalhost ? 'http://localhost:5173' : 'https://yourdomain.com/front');  // 前端網址
define('APP_ENV', getenv('APP_ENV') ?: 'dev');  // 環境：dev（開發）/ prod（正式）

// ===== 其他設定 =====
// 可以在此處加入更多全域設定，例如：
// - 上傳檔案大小限制
// - API 速率限制
// - Email 設定
// - 第三方服務 API 金鑰等
?>
