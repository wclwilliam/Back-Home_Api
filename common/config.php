<?php
/**
 * config.php
 * 全域設定檔 - 集中管理所有設定參數
 */

// ===== JWT 設定 =====
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'Lp42IPnxUf07wiK1XvaQhJyNqbRYmujoSrEg9VCtT5GHOAzc8DZksFMWlBd3e6');
define('JWT_ISS_ADMIN', 'backhome-admin');
define('JWT_ISS_MEMBER', 'backhome-member');
define('JWT_EXP_SECONDS_ADMIN', 60 * 60 * 6);  // 管理員：6 小時
define('JWT_EXP_SECONDS_MEMBER', 60 * 60 * 24 * 7);  // 會員：7 天

// ===== 驗證碼設定 =====
define('VERIFICATION_CODE_EXPIRE_MINUTES', 10);  // 驗證碼有效期限（分鐘）

// ===== 其他設定 =====
// 可以在此處加入更多全域設定，例如：
// - 上傳檔案大小限制
// - API 速率限制
// - Email 設定
// - 第三方服務 API 金鑰等
?>
