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

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_hs256($payload, $secret)
{
    $header = ["alg" => "HS256", "typ" => "JWT"];
    $segments = [];
    $segments[] = base64url_encode(json_encode($header));
    $segments[] = base64url_encode(json_encode($payload));
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, $secret, true);
    $segments[] = base64url_encode($signature);
    return implode('.', $segments);
}

// 1) 讀取 JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

// Debug: 記錄收到的資料
error_log("Received body: " . print_r($body, true));

$ADMIN_ID = isset($body['ADMIN_ID']) ? trim($body['ADMIN_ID']) : '';
$password = isset($body['ADMIN_PWD']) ? (string)$body['ADMIN_PWD'] : '';

if ($ADMIN_ID === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        "error" => "ADMIN_ID and ADMIN_PWD are required",
        "debug" => [
            "received_keys" => array_keys($body ?: []),
            "ADMIN_ID" => $ADMIN_ID,
            "has_ADMIN_PWD" => isset($body['ADMIN_PWD'])
        ]
    ]);
    exit;
}

try {
    // 2) 查帳號（不要 SELECT *，只取需要的欄位）
    $sql = "
    SELECT
      ADMIN_ID,
      ADMIN_NAME,
      ADMIN_PWD,
      ADMIN_ROLE,
      ADMIN_ACTIVE
    FROM ADMIN_USER
    WHERE ADMIN_ID = :admin_id
    LIMIT 1
  ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":admin_id" => $ADMIN_ID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3) 帳號不存在 or 停用 → 都回 401（避免洩漏資訊）
    if (!$row || (int)$row['ADMIN_ACTIVE'] !== 1) {
        http_response_code(401);
        echo json_encode(["error" => "invalid_credentials"]);
        exit;
    }

    // 4) 驗證密碼（bcrypt）
    $hash = $row['ADMIN_PWD'];
    if (!password_verify($password, $hash)) {
        http_response_code(401);
        echo json_encode(["error" => "invalid_credentials"]);
        exit;
    }

    // 5) 更新最後登入時間
    $upd = $pdo->prepare("UPDATE ADMIN_USER SET ADMIN_LAST_LOGIN_TIME = NOW() WHERE ADMIN_ID = :admin_id");
    $upd->execute([":admin_id" => $ADMIN_ID]);

    // 6) 產生 JWT
    $now = time();
    $payload = [
        "iss" => JWT_ISS_ADMIN,
        "iat" => $now,
        "exp" => $now + JWT_EXP_SECONDS_ADMIN,
        "sub" => $row['ADMIN_ID'],
        "role" => $row['ADMIN_ROLE'],
        "name" => $row['ADMIN_NAME'],
    ];
    $token = jwt_hs256($payload, JWT_SECRET);

    // 7) 回傳（不回 ADMIN_PWD）
    echo json_encode([
        "token" => $token,
        "admin" => [
            "ADMIN_ID" => $row['ADMIN_ID'],
            "ADMIN_NAME" => $row['ADMIN_NAME'],
            "ADMIN_ROLE" => $row['ADMIN_ROLE'],
        ],
    ]);

    $pdo = null;
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "server_error",
        "message" => $e->getMessage()
    ]);
    exit;
}
