<?php
require_once(__DIR__ . "/vendor/autoload.php");
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/payment");
$dotenv->safeLoad();

$domain     = 'unitpay.ru';
$projectId  = 417185;
$secretKey  = $_ENV['SECRET_KEY'];
$publicId   =  $_ENV['PUBLIC_KEY'];

$unitPay = new UnitPay($domain, $secretKey);

$response = $unitPay->api('getPayment', [
    'paymentId' => $_GET['paymentId']
]);

$mysqli = new mysqli("172.18.0.2", 'root', 'kaTei2ei', 'eternal-project');

if (isset($response->result)) {
    // Payment Info
    $paymentInfo = $response->result;


    echo $paymentInfo->account . ", " . $paymentInfo->errorMessage;
    echo "<br/><a href='/order.html'>Вернуться</a>";

    $webhookurl = "https://discord.com/api/webhooks/848280834872180796/5P5D3Cq477Ojy097N4M8ZQZpxGBwPJgNoz1beVVNvTPcdw5pznVQJfSFP7XMBNHEQFDh";
    $timestamp = date("c", strtotime("now"));
    $json_data = json_encode([
        "content" => "Ошибка заказа",
        "username" => "httpd container",
        "tts" => false,
        "embeds" => [
            [
                "title" => "Заказ :" . $paymentInfo->account,
                "description" => $paymentInfo->errorMessage,
                "type" => "rich",
                "timestamp" => $timestamp,
                "color" => hexdec("ff0569"),
                "author" => [
                    "name" => "web.eternal"
                ],
            ]
        ]

    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($webhookurl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);

    curl_close($ch);

    $stmt = $mysqli->prepare("DELETE FROM `goods` WHERE nickname = ? and time > (NOW() - INTERVAL 1 DAY) and given = 0");
    $stmt->bind_param("s", $paymentInfo->account);
    $stmt->execute();

}
