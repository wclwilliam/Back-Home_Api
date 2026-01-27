<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// 1️⃣ 讀取 JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON body"]);
    exit;
}

$email    = isset($body['email']) ? trim($body['email']) : '';
$code     = isset($body['code']) ? trim($body['code']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';
$name     = isset($body['name']) ? trim($body['name']) : '';

// 2️⃣ 基本驗證
if ($email === '' || $code === '' || $password === '' || $name === '') {
    http_response_code(400);
    echo json_encode(["error" => "email, code, password, name are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid email format"]);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(["error" => "password must be at least 8 characters"]);
    exit;
}

if (!preg_match('/[A-Z]/', $password)) {
    http_response_code(400);
    echo json_encode(["error" => "password must contain at least one uppercase letter"]);
    exit;
}
if (!preg_match('/[a-z]/', $password)) {
    http_response_code(400);
    echo json_encode(["error" => "password must contain at least one lowercase letter"]);
    exit;
}
if (!preg_match('/[0-9]/', $password)) {
    http_response_code(400);
    echo json_encode(["error" => "password must contain at least one number"]);
    exit;
}

try {
    // Transaction：避免 members 更新成功但 verification 沒標記 used（或相反）
    $pdo->beginTransaction();

    // 3️⃣ 查詢會員（建議鎖住這筆會員，避免同時啟用兩次）
    $memberSql = "SELECT member_id, member_active FROM members WHERE member_email = :email LIMIT 1 FOR UPDATE";
    $memberStmt = $pdo->prepare($memberSql);
    $memberStmt->execute([":email" => $email]);
    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "email not found, please request code first"]);
        exit;
    }

    if ((int)$member['member_active'] === 1) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(["error" => "email already registered and active"]);
        exit;
    }

    $memberId = (int)$member['member_id'];

    // 4️⃣ 查詢驗證碼記錄（未使用且未過期）並鎖住最新那筆避免重複使用
    $verifySql = "
        SELECT verification_id, code_hash, expires_at, attempts
        FROM member_email_verification
        WHERE member_id = :member_id
          AND used_at IS NULL
          AND verified_at IS NULL
          AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
        FOR UPDATE
    ";
    $verifyStmt = $pdo->prepare($verifySql);
    $verifyStmt->execute([':member_id' => $memberId]);
    $verify = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$verify) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["error" => "no valid verification code found or expired"]);
        exit;
    }

    // 5️⃣ 檢查錯誤次數(超過 3 次就鎖定)
    if ((int)$verify['attempts'] >= 3) {
        $pdo->rollBack();
        http_response_code(429);
        echo json_encode(["error" => "too many attempts, please request a new code"]);
        exit;
    }

    // 6️⃣ 驗證驗證碼（錯誤就 attempts +1）
    if (!password_verify($code, $verify['code_hash'])) {
        $updateAttempts = $pdo->prepare("
            UPDATE member_email_verification
            SET attempts = attempts + 1
            WHERE verification_id = :id
        ");
        $updateAttempts->execute([':id' => (int)$verify['verification_id']]);

        $pdo->commit(); // 讓 attempts 的更新生效（也可 rollBack 但會吃掉 attempts）
        http_response_code(401);
        echo json_encode(["error" => "invalid verification code"]);
        exit;
    }

    // 7️⃣ 密碼雜湊（bcrypt）
    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "failed to hash password"]);
        exit;
    }

    // 8️⃣ 更新會員資料並啟用
    $updateSql = "
        UPDATE members
        SET member_realname = :name,
            member_password = :password,
            member_active = 1,
            email_verified_at = NOW()
        WHERE member_id = :member_id
    ";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':name' => $name,
        ':password' => $hash,
        ':member_id' => $memberId
    ]);

    // 9️⃣ 標記驗證碼為已使用
    $markUsed = $pdo->prepare("
        UPDATE member_email_verification
        SET verified_at = NOW(),
            used_at = NOW()
        WHERE verification_id = :id
    ");
    $markUsed->execute([':id' => (int)$verify['verification_id']]);

    $pdo->commit();

    echo json_encode(["ok" => true]);
    $pdo = null;
    exit;
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        "error" => "server_error",
        "message" => $e->getMessage()
    ]);
    exit;
}
