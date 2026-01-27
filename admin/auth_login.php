<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

/**
 * ===== JWT 設定 =====
 * 建議你把 SECRET 放到環境變數或 config 檔，不要硬寫在 repo
 */
$JWT_SECRET = getenv('JWT_SECRET') ?: 'CHANGE_ME_TO_A_RANDOM_LONG_SECRET';
$JWT_ISS = 'backhome-admin';
$JWT_EXP_SECONDS = 60 * 60 * 6; // 6 小時，可自行調整

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

$admin_id = isset($body['admin_id']) ? trim($body['admin_id']) : '';
$password = isset($body['password']) ? (string)$body['password'] : '';

if ($admin_id === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["error" => "admin_id and password are required"]);
    exit;
}

try {
    // 2) 查帳號（不要 SELECT *，只取需要的欄位）
    $sql = "
    SELECT
      admin_id,
      admin_name,
      admin_pwd,
      admin_role,
      admin_active
    FROM admin_user
    WHERE admin_id = :admin_id
    LIMIT 1
  ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":admin_id" => $admin_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3) 帳號不存在 or 停用 → 都回 401（避免洩漏資訊）
    if (!$row || (int)$row['admin_active'] !== 1) {
        http_response_code(401);
        echo json_encode(["error" => "invalid_credentials"]);
        exit;
    }

    // 4) 驗證密碼（bcrypt）
    $hash = $row['admin_pwd'];
    if (!password_verify($password, $hash)) {
        http_response_code(401);
        echo json_encode(["error" => "invalid_credentials"]);
        exit;
    }

    // 5) 更新最後登入時間
    $upd = $pdo->prepare("UPDATE admin_user SET admin_last_login_time = NOW() WHERE admin_id = :admin_id");
    $upd->execute([":admin_id" => $admin_id]);

    // 6) 產生 JWT
    $now = time();
    $payload = [
        "iss" => $JWT_ISS,
        "iat" => $now,
        "exp" => $now + $JWT_EXP_SECONDS,
        "sub" => $row['admin_id'],
        "role" => $row['admin_role'],
        "name" => $row['admin_name'],
    ];
    $token = jwt_hs256($payload, $JWT_SECRET);

    // 7) 回傳（不回 admin_pwd）
    echo json_encode([
        "token" => $token,
        "admin" => [
            "admin_id" => $row['admin_id'],
            "admin_name" => $row['admin_name'],
            "admin_role" => $row['admin_role'],
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
