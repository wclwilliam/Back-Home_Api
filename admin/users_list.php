<?php
require_once("../common/cors.php");
require_once("../common/conn.php");
require_once("../common/config_loader.php");
require_once("./auth_guard.php");

header('Content-Type: application/json; charset=utf-8');

// 驗證管理員身份
$admin = requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- 1. 讀取 query 參數 ----------
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? max(1, (int)$_GET['pageSize']) : 10;
$keyword = isset($_GET['keyword']) ? trim((string)$_GET['keyword']) : '';
$sortBy = isset($_GET['sortBy']) ? (string)$_GET['sortBy'] : '';

$offset = ($page - 1) * $pageSize;
$limit = (int)$pageSize;
$off   = (int)$offset;

// ---------- 2. sortBy 對應 SQL ----------
$orderBy = "ADMIN_CREATED_AT DESC";

switch ($sortBy) {
    case 'created_at_asc':
        $orderBy = "ADMIN_CREATED_AT ASC";
        break;
    case 'created_at_desc':
        $orderBy = "ADMIN_CREATED_AT DESC";
        break;
    case 'last_login_asc':
        $orderBy = "ADMIN_LAST_LOGIN_TIME ASC";
        break;
    case 'last_login_desc':
        $orderBy = "ADMIN_LAST_LOGIN_TIME DESC";
        break;
    case 'name_asc':
        $orderBy = "ADMIN_NAME ASC";
        break;
    case 'name_desc':
        $orderBy = "ADMIN_NAME DESC";
        break;
    case 'status_enabled_first':
        $orderBy = "ADMIN_ACTIVE DESC";
        break;
    case 'status_disabled_first':
        $orderBy = "ADMIN_ACTIVE ASC";
        break;
}

// ---------- 3. WHERE 條件（搜尋） ----------
$hasKeyword = ($keyword !== '');
// 修改點：將佔位符分開，避免重複使用同一個名稱
$whereSQL = $hasKeyword ? "WHERE ADMIN_ID LIKE :kw1 OR ADMIN_NAME LIKE :kw2" : "";

// ---------- 4. & 5. 執行資料庫查詢（加入錯誤捕捉） ----------
try {
    // 建立統一的參數陣列
    $params = [];
    if ($hasKeyword) {
        $kw_value = "%{$keyword}%";
        // 修改點：對應 SQL 裡的兩個不同佔位符
        $params[':kw1'] = $kw_value;
        $params[':kw2'] = $kw_value;
    }

    // 取得總筆數
    $countSQL = "SELECT COUNT(*) FROM ADMIN_USER {$whereSQL}";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // 取得清單資料
    $listSQL = "
      SELECT
        ADMIN_ID,
        ADMIN_NAME,
        ADMIN_ROLE,
        ADMIN_ACTIVE,
        ADMIN_CREATED_AT,
        ADMIN_LAST_LOGIN_TIME
      FROM ADMIN_USER
      {$whereSQL}
      ORDER BY {$orderBy}
      LIMIT {$limit} OFFSET {$off}
    ";

    $listStmt = $pdo->prepare($listSQL);
    $listStmt->execute($params);
    $rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    // ---------- 6. 成功回傳 ----------
    echo json_encode([
        "items" => $rows,
        "pagination" => [
            "page" => $page,
            "pageSize" => $pageSize,
            "total" => $total
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    // 如果發生錯誤，回傳 500 錯誤碼與 JSON 訊息，而不是噴出 HTML Fatal Error
    http_response_code(500);
    echo json_encode([
        "error" => "資料庫查詢失敗",
        "message" => $e->getMessage() // 開發階段可以看具體錯誤，上線後建議拿掉
    ], JSON_UNESCAPED_UNICODE);
}

$pdo = null;
exit;
