<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Метод не поддерживается']);
    exit;
}

$city = $_POST['city'] ?? '';
$weight = isset($_POST['weight']) ? (int)$_POST['weight'] : 0;

if (empty($city) || $weight <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Некорректные параметры']);
    exit;
}

$username = 'cli';
$password = '12344321';

$url = 'http://exercise.develop.maximaster.ru/service/delivery/?' . http_build_query([
    'city' => $city,
    'weight' => $weight
]);

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n" . 
                    "Authorization: Basic " . base64_encode("$username:$password"),
        'ignore_errors' => true
    ]
    ]);

$response = @file_get_contents($url, false, $context);
if ($response === false){
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка обращения к сервису доставки']);
    exit;
}

$result = json_decode($response, true);
if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Ошибка обработки ответа сервиса']);
    exit;
}

echo json_encode($result);
?>