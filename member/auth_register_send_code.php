<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON body"]);
    exit;
}

$email = isset($body['email']) ? trim($body['email']) : '';

if ($email === '') {
    http_response_code(400);
    echo json_encode(["error" => "email is required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid email format"]);
    exit;
}

// === 設定：驗證碼有效時間 ===
$EXPIRE_MINUTES = defined('VERIFICATION_CODE_EXPIRE_MINUTES') ? (int)VERIFICATION_CODE_EXPIRE_MINUTES : 5;

// === 設定：Demo 模式（上線建議 false） ===
$DEMO_RETURN_CODE = false;

try {
    // 用交易包起來，避免併發情境下 insert 會員/刪驗證碼 出現不一致
    $pdo->beginTransaction();

    // 1) 檢查 email 是否已註冊
    $checkMember = $pdo->prepare("SELECT member_id, member_active FROM members WHERE member_email = :email LIMIT 1");
    $checkMember->execute([":email" => $email]);
    $existingMember = $checkMember->fetch(PDO::FETCH_ASSOC);

    if ($existingMember) {
        if ((int)$existingMember['member_active'] === 1) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(["error" => "email already registered"]);
            exit;
        }

        $memberId = (int)$existingMember['member_id'];

        // 未啟用允許重新申請：清除舊驗證記錄（最簡做法）
        $deleteOld = $pdo->prepare("DELETE FROM member_email_verification WHERE member_id = :member_id");
        $deleteOld->execute([':member_id' => $memberId]);
    } else {
        // 2) 建立臨時會員記錄（未啟用）
        $tempPwd = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        if ($tempPwd === false) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "failed to hash code"]);
            exit;
        }

        $insertMember = $pdo->prepare("
            INSERT INTO MEMBERS (MEMBER_EMAIL, MEMBER_REALNAME, MEMBER_PASSWORD, MEMBER_ACTIVE)
            VALUES (:email, '', :pwd, 0)
        ");
        $insertMember->execute([
            ':email' => $email,
            ':pwd' => $tempPwd
        ]);
        $memberId = (int)$pdo->lastInsertId();
    }

    // 3) 產生 6 碼驗證碼（可含前導 0）
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // 4) 雜湊存 DB（bcrypt）
    $codeHash = password_hash($code, PASSWORD_BCRYPT);
    if ($codeHash === false) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "failed to hash code"]);
        exit;
    }

    // 5) 用 PHP 算 expires_at（避免 INTERVAL 綁參數雷）
    $expiresAt = (new DateTime())->modify("+{$EXPIRE_MINUTES} minutes")->format('Y-m-d H:i:s');

    // 6) 寫入驗證表（未使用、未驗證）
    $sql = "
        INSERT INTO MEMBER_EMAIL_VERIFICATION (MEMBER_ID, CODE_HASH, EXPIRES_AT)
        VALUES (:member_id, :code_hash, :expires_at)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':member_id' => $memberId,
        ':code_hash' => $codeHash,
        ':expires_at' => $expiresAt,
    ]);

    $pdo->commit();

    // 7) 使用 Brevo API 寄送驗證碼 Email
    $apiKey = defined('BREVO_API_KEY') ? (string)BREVO_API_KEY : '';
    $baseUrl = defined('BREVO_BASE_URL') ? rtrim((string)BREVO_BASE_URL, '/') : 'https://api.brevo.com';

    if ($apiKey === '' || $apiKey === 'CHANGE_ME') {
        http_response_code(500);
        echo json_encode(["error" => "server_error", "message" => "brevo_api_key_missing"]);
        exit;
    }

    $payload = [
        'sender' => [
            'email' => defined('BREVO_FROM_EMAIL') ? BREVO_FROM_EMAIL : 'no-reply@example.com',
            'name' => defined('BREVO_FROM_NAME') ? BREVO_FROM_NAME : 'BackHome',
        ],
        'to' => [
            [ 'email' => $email ]
        ],
        'subject' => '驗證碼',
        'htmlContent' => "<p>您的驗證碼為：<strong>{$code}</strong></p><p>有效時間 {$EXPIRE_MINUTES} 分鐘。</p>",
        'textContent' => "您的驗證碼為：{$code}，有效時間 {$EXPIRE_MINUTES} 分鐘。"
    ];

    if (!function_exists('curl_init')) {
        http_response_code(500);
        echo json_encode(["error" => "server_error", "message" => "curl_not_enabled"]);
        exit;
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
        http_response_code(500);
        echo json_encode(["error" => "server_error", "message" => "curl_failed", "detail" => $curlErr]);
        exit;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        http_response_code(500);
        echo json_encode(["error" => "server_error", "message" => "email_send_failed", "detail" => $resp]);
        exit;
    }

    $res = ["ok" => true];
    if ($DEMO_RETURN_CODE) $res["code"] = $code;
    // 也可以順便回傳 expires_in 給前端倒數用（選擇性）
    // $res["expires_in"] = $EXPIRE_MINUTES * 60;

    echo json_encode($res);
    $pdo = null;
    exit;

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "server_error", "message" => $e->getMessage()]);
    exit;
}
