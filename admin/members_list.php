<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

// 驗證管理員身份
$admin = requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 可選：支援簡易分頁、搜尋與排序
 * - page: 頁碼（預設 1）
 * - pageSize: 每頁筆數（預設 10，最大 100）
 * - q: 搜尋關鍵字（姓名/Email）
 * - sortBy: 排序方式
 */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$sortBy = isset($_GET['sortBy']) ? trim((string)$_GET['sortBy']) : '';

if ($page < 1) $page = 1;
if ($pageSize < 1) $pageSize = 10;
if ($pageSize > 100) $pageSize = 100;

$offset = ($page - 1) * $pageSize;

// 排序邏輯（預設：會員編號由小到大）
$orderBy = 'MEMBER_ID ASC';
$usePhpSort = false;

switch ($sortBy) {
    case 'created_desc':
        $orderBy = 'MEMBER_CREATED_AT DESC';
        break;
    case 'created_asc':
        $orderBy = 'MEMBER_CREATED_AT ASC';
        break;
    case 'id_asc':
        $orderBy = 'MEMBER_ID ASC';
        break;
    case 'id_desc':
        $orderBy = 'MEMBER_ID DESC';
        break;
    case 'name_asc':
    case 'name_desc':
        // 姓名排序：直接用 MySQL 的 utf8mb4_zh_0900_as_cs（MySQL 8.0+）
        // 或退而求其次用 CONVERT 函數
        $orderBy = 'MEMBER_REALNAME ' . ($sortBy === 'name_desc' ? 'DESC' : 'ASC');
        break;
    case 'active_first':
        $orderBy = 'MEMBER_ACTIVE DESC, MEMBER_CREATED_AT DESC';
        break;
    case 'inactive_first':
        $orderBy = 'MEMBER_ACTIVE ASC, MEMBER_CREATED_AT DESC';
        break;
}

try {
    $whereSql = '';
    $params = [];

    if ($q !== '') {
        $whereSql = "WHERE MEMBER_REALNAME LIKE :q1 OR MEMBER_EMAIL LIKE :q2 OR MEMBER_PHONE LIKE :q3";
        $params[':q1'] = '%' . $q . '%';
        $params[':q2'] = '%' . $q . '%';
        $params[':q3'] = '%' . $q . '%';
    }

    // 1) 先拿總筆數（方便前端做分頁）
    $countSql = "SELECT COUNT(*) AS total FROM MEMBERS {$whereSql}";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $total = (int)($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // 2) 查列表（注意：不要回傳 MEMBER_PASSWORD）
    $sql = "
        SELECT
            MEMBER_ID,
            MEMBER_REALNAME,
            MEMBER_EMAIL,
            MEMBER_PHONE,
            ID_NUMBER,
            BIRTHDAY,
            EMERGENCY,
            EMERGENCY_TEL,
            EMAIL_VERIFIED_AT,
            MEMBER_ACTIVE,
            MEMBER_CREATED_AT
        FROM MEMBERS
        {$whereSql}
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    // bindValue 才能綁 int 的 limit/offset
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 為每筆資料添加狀態文字
    foreach ($rows as &$row) {
        $row['MEMBER_ACTIVE_TEXT'] = (int)$row['MEMBER_ACTIVE'] === 1 ? '啟用' : '停用';
    }
    unset($row);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "items" => $rows,
        "pagination" => [
            "page" => $page,
            "pageSize" => $pageSize,
            "total" => $total
        ]
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
