<?php

// === CORS 設定 ===
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 

// 關閉錯誤顯示
error_reporting(0);
ini_set('display_errors', 0);

// === 設定 API 資訊 ===
$apiKey = '7dbd629b-6cac-4272-b072-93a8fac149a0';
$apiUrl = 'https://iocean.oca.gov.tw/oca_datahub/WebService/GetData.ashx';

// 12 個月份的 Source IDs
$sourceIds = [
    'ce2b42ac-c343-4df7-aa02-68e22d81311f', // 114.10
    '31cda9f1-fbbe-4b65-91fc-dc86d71dc519', // 114.09
    '4a9345e3-3f03-41d6-a5eb-9f741fb91fd4', // 114.08
    '48d82abd-577d-4aec-b5f1-25cec76ebe5b', // 114.07
    'c08e8ef7-9ace-491e-8cf1-d9dd8d66c506', // 114.06
    'efb76d92-d880-49f0-abdd-d64e0c9f6ccc', // 114.05
    'c725b662-2436-422f-a9e8-9cbd1050d807', // 114.04
    '312f1044-420f-470d-82ef-7a2af3262c46',
    'e04f7f5b-38ba-465b-a4a0-172595a963d6',
    'd4985722-bc74-4ee8-8362-c9edd3d28283',
    '9aa1be1a-55e6-4919-9407-513aa8122938',
    '4b6798e3-291e-48b7-87fd-ec68b73a102a',
];


// === 檢查 curl 是否可用 ===
if (!function_exists('curl_init')) {
    echo json_encode([
        "status" => "error",
        "message" => "伺服器不支援 curl",
        "data" => [
            "plastic_sea" => ["value" => 8000000],
            "ghost_gear" => ["value" => 640000],
            "bycatch" => ["value" => 9100000],
            "lives_lost" => ["value" => 1000000]
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// === 核心：使用 curl_multi 平行抓取 ===
$mh = curl_multi_init();
$curl_handles = [];

// 1. 建立所有請求
foreach ($sourceIds as $id) {
    $ch = curl_init();
    $url = $apiUrl . '?id=' . $id;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 增加超時時間
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["API-KEY: " . $apiKey]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 允許重定向
    
    curl_multi_add_handle($mh, $ch);
    $curl_handles[$id] = $ch;
}

// 2. 同時執行所有請求
$running = null;
do {
    $status = curl_multi_exec($mh, $running);
    if ($running) {
        curl_multi_select($mh, 0.1);
    }
} while ($running > 0);

// 3. 收集結果並計算總重
$plastic_total = 0;
$success_count = 0;
$error_count = 0;
$debug_info = [];

foreach ($curl_handles as $id => $ch) {
    $response = curl_multi_getcontent($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    // 記錄每個請求的狀態
    $debug_info[$id] = [
        'http_code' => $http_code,
        'has_response' => !empty($response),
        'error' => $curl_error
    ];
    
    if ($curl_error) {
        $error_count++;
    } elseif ($http_code !== 200) {
        $error_count++;
    } else {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_count++;
        } elseif (is_array($data) && !isset($data['error'])) {
            $item_count = 0;
            foreach ($data as $item) {
                if (isset($item['清理數量分類(噸)_總計'])) {
                    $weight = str_replace(',', '', $item['清理數量分類(噸)_總計']);
                    $plastic_total += floatval($weight);
                    $item_count++;
                }
            }
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    // 清理資源
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// === 處理預設值與格式化 ===
$using_fallback = false;
if ($plastic_total == 0) {
    $plastic_total = 8000000;
    $using_fallback = true;
}

// 最終輸出
$final_output = [
    "status" => "success",
    "using_fallback" => $using_fallback,
    "api_stats" => [
        "success" => $success_count,
        "failed" => $error_count,
        "total" => count($sourceIds)
    ],
    "data" => [
        "plastic_sea" => [
            "value" => round($plastic_total, 0),
        ],
        "ghost_gear" => ["value" => 640000],
        "bycatch" => ["value" => 9100000],
        "lives_lost" => ["value" => 1000000]
    ]
];

// 開發模式：加入除錯資訊
if (isset($_GET['debug'])) {
    $final_output['debug'] = $debug_info;
}

echo json_encode($final_output, JSON_UNESCAPED_UNICODE);
?>