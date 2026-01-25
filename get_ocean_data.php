<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, API-KEY");
    header("Access-Control-Max-Age: 86400");
    http_response_code(200);
    exit(0);
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, API-KEY");
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

// === 快取設定 ===
$cache_file = __DIR__ . '/ocean_data_cache.json';
$cache_duration = 3600; // 1小時 (3600秒)

// 檢查是否強制更新
$force_refresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';

// 檢查快取
if (!$force_refresh && file_exists($cache_file)) {
    $cache_age = time() - filemtime($cache_file);
    
    if ($cache_age < $cache_duration) {
        $cached_data = file_get_contents($cache_file);
        echo $cached_data;
        exit;
    }
}


$config = [
    'oca_api_key' => '7dbd629b-6cac-4272-b072-93a8fac149a0',
    'oca_api_url' => 'https://iocean.oca.gov.tw/oca_datahub/WebService/GetData.ashx',
    'source_ids' => [
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
    ]
];

function fetch_data($url, $headers = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => 'CURL Error: ' . $error];
    }

    if ($http_code !== 200) {
        return ['error' => 'HTTP Error: ' . $http_code];
    }

    return json_decode($res, true);
}

$headers = ["API-KEY: " . $config['oca_api_key']];
$plastic_total = 0;
$total_records = 0;
$api_calls = [];

foreach ($config['source_ids'] as $source_id) {
    $api_url = $config['oca_api_url'] . '?id=' . $source_id;
    $oca_raw = fetch_data($api_url, $headers);

    $call_result = [
        'source_id' => $source_id,
        'success' => false,
        'records' => 0,
        'weight' => 0
    ];

    if (isset($oca_raw['error'])) {
        $call_result['error'] = $oca_raw['error'];
    } elseif (is_array($oca_raw) && !empty($oca_raw)) {
        $call_result['success'] = true;
        $call_result['records'] = count($oca_raw);
        $total_records += count($oca_raw);

        foreach ($oca_raw as $item) {
            if (isset($item['清理數量分類(噸)_總計'])) {
                $weight = str_replace(',', '', $item['清理數量分類(噸)_總計']);
                $weight_value = floatval($weight);
                $plastic_total += $weight_value;
                $call_result['weight'] += $weight_value;
            }
        }
    }

    $api_calls[] = $call_result;
}

if ($plastic_total == 0) {
    $plastic_total = 8000000;
}

$gear_total = 640000;
$bycatch_total = 9100000;
$lives_total = 1000000;

$final_output = [
    "status" => "success",
    "timestamp" => date("Y-m-d H:i:s"),
    "cached" => false, // 新抓取的資料
    "data" => [
        "plastic_sea" => [
            "value" => round($plastic_total, 0),
            "unit" => "Tonnes",
            "label" => "台灣海洋廢棄物清理總量(114年度)",
            "source" => "iOcean 海洋保育署"
        ],
        "ghost_gear" => [
            "value" => $gear_total,
            "unit" => "Tonnes",
            "label" => "幽靈漁具"
        ],
        "bycatch" => [
            "value" => $bycatch_total,
            "unit" => "Tonnes",
            "label" => "混獲浪費"
        ],
        "lives_lost" => [
            "value" => $lives_total,
            "unit" => "Lives",
            "label" => "無辜生命殞落"
        ]
    ],
    "debug" => [
        "total_api_calls" => count($config['source_ids']),
        "successful_calls" => count(array_filter($api_calls, function ($call) {
            return $call['success'];
        })),
        "total_records" => $total_records,
        "calculated_total" => $plastic_total,
        "api_calls_detail" => $api_calls
    ]
];

$json_output = json_encode($final_output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// 儲存
file_put_contents($cache_file, $json_output);

// 輸出
echo $json_output;
exit;
?>