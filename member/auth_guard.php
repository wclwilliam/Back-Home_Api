<?php
declare(strict_types=1);

/**
 * auth_guard.php
 * 用途：
 * - 讀取 Authorization: Bearer <JWT>
 * - 驗證 JWT（HS256）：簽章 + exp
 * - 回傳 member_id（int）
 *
 * 使用方式：
 * require_once("../common/cors.php");
 * require_once("../common/conn.php");
 * require_once("../common/config.php");
 * require_once("../member/auth_guard.php");
 * $memberId = requireAuth($pdo);
 */

require_once(__DIR__ . "/../common/config_loader.php");

if (!function_exists('auth_json_out')) {
    function auth_json_out(int $code, array $payload): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('auth_base64url_decode')) {
    function auth_base64url_decode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $data = strtr($data, '-_', '+/');
        return base64_decode($data, true);
    }
}

if (!function_exists('auth_base64url_encode')) {
    function auth_base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('auth_jwt_verify_hs256')) {
    /**
     * 驗證 HS256 JWT：回傳 payload array（成功）或 false（失敗）
     */
    function auth_jwt_verify_hs256(string $token, string $secret): array|false
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        [$h64, $p64, $s64] = $parts;

        $headerJson = auth_base64url_decode($h64);
        $payloadJson = auth_base64url_decode($p64);
        $sig = auth_base64url_decode($s64);

        if ($headerJson === false || $payloadJson === false || $sig === false) return false;

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) return false;

        // 只接受 HS256
        if (($header['alg'] ?? '') !== 'HS256') return false;

        // 比對簽章（constant-time compare）
        $signingInput = $h64 . '.' . $p64;
        $expectedSig = hash_hmac('sha256', $signingInput, $secret, true);

        if (!hash_equals($expectedSig, $sig)) return false;

        // exp 檢查
        if (!isset($payload['exp']) || !is_numeric($payload['exp'])) return false;
        if ((int)$payload['exp'] < time()) return false;

        return $payload;
    }
}

if (!function_exists('requireAuth')) {
    /**
     * requireAuth
     * - 成功：回傳 member_id
     * - 失敗：直接 401/500 並 exit
     *
     * @param PDO $pdo 用來做（可選）的 member_active 檢查
     * @param bool $checkActive 是否檢查 members.member_active（建議 true）
     */
    function requireAuth(PDO $pdo, bool $checkActive = true): int
    {
        if (!defined('JWT_SECRET') || trim((string)JWT_SECRET) === '') {
            auth_json_out(500, ['error' => 'server_misconfigured']);
        }

        // 取得 Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($authHeader === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if ($authHeader === '' || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
            auth_json_out(401, ['error' => 'missing token']);
        }

        $token = trim($m[1]);

        $payload = auth_jwt_verify_hs256($token, (string)JWT_SECRET);
        if ($payload === false) {
            auth_json_out(401, ['error' => 'invalid token']);
        }

        if (!isset($payload['member_id']) || !is_numeric($payload['member_id'])) {
            auth_json_out(401, ['error' => 'invalid token']);
        }

        $memberId = (int)$payload['member_id'];

        // （建議）額外檢查 member_active：避免停權的人還能用沒過期的 JWT
        if ($checkActive) {
            $stmt = $pdo->prepare("
                SELECT MEMBER_ACTIVE
                FROM MEMBERS
                WHERE MEMBER_ID = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $memberId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                auth_json_out(401, ['error' => 'invalid token']);
            }
            if ((int)$row['MEMBER_ACTIVE'] !== 1) {
                auth_json_out(403, ['error' => 'account is inactive']);
            }
        }

        return $memberId;
    }
}
