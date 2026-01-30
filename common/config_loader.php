<?php
// config_loader.php
// 先載入本機 config.php；若不存在則載入範本 config.example.php

$localConfig = __DIR__ . '/config.php';
$exampleConfig = __DIR__ . '/config.example.php';

if (file_exists($localConfig)) {
    require_once($localConfig);
} elseif (file_exists($exampleConfig)) {
    require_once($exampleConfig);
} else {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'server_error',
        'message' => 'config file missing'
    ]);
    exit;
}
