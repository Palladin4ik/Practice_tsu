<?php
header('Content-Type: application/json');

$cacheFile = 'cities_cache.json';
$cacheTime = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
$today = date('Y-m-d');

$username = 'cli';
$password = '12344321';

if ($cacheTime && date('Y-m-d', $cacheTime) === $today) {
    $cities = json_decode(file_get_contents($cacheFile), true);
} else {
    $url = 'http://exercise.develop.maximaster.ru/service/city/';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n" . 
                        "Authorization: Basic " . base64_encode("$username:$password"),
            'ignore_errors' => true
        ]
        ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка получения списка городов']);
        exit;
    }

    $cities = json_decode($response, true);
    if ($cities) {
        file_put_contents($cacheFile, json_encode($cities));
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка декодирования списка городов']);
        exit;
    }
}

echo json_encode($cities);
?>