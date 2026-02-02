<?php
/**
 * admin/auth_guard.php
 * 用途：
 * - 讀取 Authorization: Bearer <JWT>
 * - 驗證 JWT（HS256）：簽章 + exp + iss
 * - 回傳管理員資訊（admin_id, role, name）
 *
 * 使用方式：
 * require_once("../common/cors.php");
 * require_once("../common/conn.php");
 * require_once("../common/config.php");
 * require_once("../admin/auth_guard.php");
 * $admin = requireAdminAuth();
 */

require_once(__DIR__ . "/../common/config_loader.php");

if (!function_exists('admin_json_out')) {
    function admin_json_out(int $code, array $payload): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('admin_base64url_decode')) {
    function admin_base64url_decode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('admin_jwt_verify_hs256')) {
    /**
     * 驗證 JWT (HS256)
     * @param string $token JWT token
     * @param string $secret 密鑰
     * @return array|null 成功返回 payload，失敗返回 null
     */
    function admin_jwt_verify_hs256(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // 1) 驗證簽章
        $signing_input = $headerB64 . '.' . $payloadB64;
        $expected_signature = hash_hmac('sha256', $signing_input, $secret, true);
        $expected_signature_b64 = rtrim(strtr(base64_encode($expected_signature), '+/', '-_'), '=');

        if (!hash_equals($expected_signature_b64, $signatureB64)) {
            return null; // 簽章不符
        }

        // 2) 解析 payload
        $payloadJson = admin_base64url_decode($payloadB64);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        // 3) 檢查 exp
        if (isset($payload['exp']) && is_numeric($payload['exp'])) {
            if (time() > (int)$payload['exp']) {
                return null; // 已過期
            }
        }

        // 4) 檢查 iss（發行者）
        if (!isset($payload['iss']) || $payload['iss'] !== JWT_ISS_ADMIN) {
            return null; // 不是管理員 token
        }

        return $payload;
    }
}

if (!function_exists('requireAdminAuth')) {
    /**
     * 要求管理員身份驗證
     * @return array 包含 admin_id, role, name 的陣列
     */
    function requireAdminAuth(): array
    {
        // 1) 取得 Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            // 嘗試從 apache_request_headers() 取得
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authHeader = $headers['Authorization'] ?? '';
            }
        }

        if (empty($authHeader)) {
            admin_json_out(401, ["error" => "unauthorized", "message" => "Missing Authorization header"]);
        }

        // 2) 解析 Bearer token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            admin_json_out(401, ["error" => "unauthorized", "message" => "Invalid Authorization format"]);
        }

        $token = $matches[1];

        // 3) 驗證 JWT
        if (!defined('JWT_SECRET') || trim((string)JWT_SECRET) === '') {
            admin_json_out(500, ["error" => "server_error", "message" => "JWT_SECRET not configured"]);
        }

        $payload = admin_jwt_verify_hs256($token, (string)JWT_SECRET);

        if ($payload === null) {
            admin_json_out(401, ["error" => "unauthorized", "message" => "Invalid or expired token"]);
        }

        // 4) 返回管理員資訊
        return [
            'admin_id' => $payload['sub'] ?? '',
            'role' => $payload['role'] ?? '',
            'name' => $payload['name'] ?? ''
        ];
    }
}
?>
