<?php
require_once("../common/cors.php");
require_once("../common/conn.php");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// ---------- 1. 讀取 query 參數 ----------
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? max(1, (int)$_GET['pageSize']) : 10;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : '';

$offset = ($page - 1) * $pageSize;

// ---------- 2. sortBy 對應 SQL ----------
$orderBy = "admin_created_at DESC"; // 預設排序

switch ($sortBy) {
    case 'created_at_asc':
        $orderBy = "admin_created_at ASC";
        break;

    case 'created_at_desc':
        $orderBy = "admin_created_at DESC";
        break;

    case 'last_login_asc':
        $orderBy = "admin_last_login_time ASC";
        break;

    case 'last_login_desc':
        $orderBy = "admin_last_login_time DESC";
        break;

    case 'name_asc':
        $orderBy = "admin_name ASC";
        break;

    case 'name_desc':
        $orderBy = "admin_name DESC";
        break;

    case 'status_enabled_first':
        $orderBy = "admin_active DESC";
        break;

    case 'status_disabled_first':
        $orderBy = "admin_active ASC";
        break;
}

// ---------- 3. WHERE 條件（搜尋） ----------
$whereSQL = "";
$params = [];

if ($keyword !== '') {
    $whereSQL = "WHERE admin_id LIKE :kw OR admin_name LIKE :kw";
    $params[':kw'] = "%{$keyword}%";
}

// ---------- 4. 取得總筆數 ----------
$countSQL = "SELECT COUNT(*) FROM admin_user {$whereSQL}";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// ---------- 5. 取得資料 ----------
$listSQL = "
  SELECT
    admin_id,
    admin_name,
    admin_role,
    admin_active,
    admin_created_at,
    admin_last_login_time
  FROM admin_user
  {$whereSQL}
  ORDER BY {$orderBy}
  LIMIT :limit OFFSET :offset
";

$listStmt = $pdo->prepare($listSQL);

// bind 搜尋參數
foreach ($params as $key => $val) {
    $listStmt->bindValue($key, $val);
}

// bind 分頁參數（一定要指定型別）
$listStmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$listStmt->execute();
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- 6. 回傳 ----------
header('Content-Type: application/json');
echo json_encode([
    "items" => $rows,
    "pagination" => [
        "page" => $page,
        "pageSize" => $pageSize,
        "total" => $total
    ]
]);

$pdo = null;
exit;
