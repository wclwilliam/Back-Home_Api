<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證會員身份，取得登入者 member_id
$memberId = requireAuth($pdo);

// 只允許 PATCH
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 讀取 JSON body（PATCH 也是從 php://input 讀）
 */
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Invalid JSON body"], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 白名單：允許更新欄位（全部小寫）
 */
$allowed = [
    'member_realname',
    'member_phone',
    'id_number',
    'birthday',
    'emergency',
    'emergency_tel',
];

/**
 * 基本清理 & 驗證（簡單實用版）
 */
function normalize_nullable_string($val): ?string
{
    if (!is_string($val) && !is_numeric($val)) return null;
    $s = trim((string)$val);
    return $s === '' ? null : $s;
}

function is_valid_date_yyyy_mm_dd(?string $date): bool
{
    if ($date === null) return true; // 允許清空
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    [$y, $m, $d] = explode('-', $date);
    return checkdate((int)$m, (int)$d, (int)$y);
}

function is_valid_phone(?string $phone): bool
{
    if ($phone === null) return true; // 允許清空
    // 台灣手機常見：09xxxxxxxx；市話格式很多，這裡先給寬鬆版
    return (bool)preg_match('/^[0-9+\-() ]{6,20}$/', $phone);
}

function is_valid_id_number(?string $id): bool
{
    if ($id === null) return true; // 允許清空
    // 台灣身分證常見格式：1 英文字母 + 9 數字（你也可以改更嚴格）
    return (bool)preg_match('/^[A-Z][0-9]{9}$/i', $id);
}

try {
    /**
     * 1) 組 UPDATE 欄位（只更新有傳且在白名單內的）
     */
    $setParts = [];
    $params = [":member_id" => $memberId];

    foreach ($allowed as $key) {
        if (!array_key_exists($key, $body)) continue;

        // 統一做 trim，空字串 -> NULL（代表清空欄位）
        $val = normalize_nullable_string($body[$key]);

        // 欄位驗證
        if ($key === 'birthday' && !is_valid_date_yyyy_mm_dd($val)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error" => "invalid birthday format (YYYY-MM-DD)"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (($key === 'member_phone' || $key === 'emergency_tel') && !is_valid_phone($val)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error" => "invalid phone format"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($key === 'id_number' && !is_valid_id_number($val)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error" => "invalid id_number format"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 產生 SET 片段
        $paramName = ":" . $key;
        $setParts[] = "{$key} = {$paramName}";
        $params[$paramName] = $val; // PDO 會把 null 正確寫入
    }

    // 沒有任何可更新欄位
    if (count($setParts) === 0) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "error" => "no updatable fields",
            "allowed" => $allowed
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 2) 執行 UPDATE
     */
    $sql = "
        UPDATE members
        SET " . implode(", ", $setParts) . "
        WHERE member_id = :member_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    /**
     * 3) 回傳更新後資料（可選，但會員中心很常需要）
     * 注意：不要回傳 member_password
     */
    $selectSql = "
        SELECT
            member_id,
            member_realname,
            member_email,
            member_phone,
            id_number,
            birthday,
            emergency,
            emergency_tel,
            member_active
        FROM members
        WHERE member_id = :member_id
        LIMIT 1
    ";
    $stmt2 = $pdo->prepare($selectSql);
    $stmt2->execute([":member_id" => $memberId]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "member not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "success",
        "member" => $row
    ], JSON_UNESCAPED_UNICODE);

    $pdo = null;
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "error" => "server_error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
