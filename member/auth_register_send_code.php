<?php
require_once("./common/cors.php");
require_once("./common/conn.php");

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
$EXPIRE_MINUTES = 5;

// === 設定：Demo 模式（true 會回傳 code，方便你測試；上線改 false） ===
$DEMO_RETURN_CODE = true;

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
            INSERT INTO members (member_email, member_realname, member_password, member_active)
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
        INSERT INTO member_email_verification (member_id, code_hash, expires_at)
        VALUES (:member_id, :code_hash, :expires_at)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':member_id' => $memberId,
        ':code_hash' => $codeHash,
        ':expires_at' => $expiresAt,
    ]);

    $pdo->commit();

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
