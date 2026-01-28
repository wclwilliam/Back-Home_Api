<?php
// get_ocean_data.php

// === CORS 設定 ===
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// 關閉錯誤顯示以免破壞 JSON 格式
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

// === 核心：使用 curl_multi 平行抓取 ===
$mh = curl_multi_init();
$curl_handles = [];

// 1. 建立所有請求
foreach ($sourceIds as $id) {
    $ch = curl_init();
    $url = $apiUrl . '?id=' . $id;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 設定超時
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["API-KEY: " . $apiKey]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_multi_add_handle($mh, $ch);
    $curl_handles[$id] = $ch;
}

// 2. 同時執行所有請求
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

// 3. 收集結果並計算總重
$plastic_total = 0;

foreach ($curl_handles as $id => $ch) {
    $response = curl_multi_getcontent($ch);
    $data = json_decode($response, true);
    
    // 簡單的錯誤檢查與累加
    if (is_array($data) && !isset($data['error'])) {
        foreach ($data as $item) {
            if (isset($item['清理數量分類(噸)_總計'])) {
                $weight = str_replace(',', '', $item['清理數量分類(噸)_總計']);
                $plastic_total += floatval($weight);
            }
        }
    }
    
    // 清理資源
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// === 處理預設值與格式化 ===
if ($plastic_total == 0) {
    $plastic_total = 8000000; // 如果 API 全掛，使用預設值
}

// 這裡維持你原本的資料結構，這樣 Vue 不用改
$final_output = [
    "status" => "success",
    "data" => [
        "plastic_sea" => [
            "value" => round($plastic_total, 0),
        ],
        "ghost_gear" => ["value" => 640000],
        "bycatch" => ["value" => 9100000],
        "lives_lost" => ["value" => 1000000]
    ]
];

echo json_encode($final_output, JSON_UNESCAPED_UNICODE);
?>