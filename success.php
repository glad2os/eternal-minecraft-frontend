<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once(__DIR__ . "/vendor/autoload.php");
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/payment");
$dotenv->safeLoad();

$domain = 'unitpay.ru';
$projectId = 417185;
$secretKey = $_ENV['SECRET_KEY'];
$publicId = $_ENV['PUBLIC_KEY'];

$unitPay = new UnitPay($domain, $secretKey);

$response = $unitPay->api('getPayment', [
    'paymentId' => $_GET['paymentId']
]);
$mysqli = new mysqli("172.18.0.2", 'root', 'kaTei2ei', 'eternal-project');
// If need user redirect on Payment Gate
if (isset($response->result)) {
    // Payment Info

    $paymentInfo = $response->result;

    $stmt = $mysqli->prepare("INSERT INTO `orders`(`date`, `status`, `goods_id`, `paymentId`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $paymentInfo->date, $paymentInfo->status, $_SESSION['id'], $paymentInfo->paymentId);
    $stmt->execute();


    if ($stmt2 = $mysqli->prepare("select * from orders left join goods g on orders.goods_id = g.id where goods_id = ?")) {
        $stmt2->bind_param('i', $_SESSION['id']);
        $stmt2->execute();

        $result = $stmt2->get_result()->fetch_assoc();
    }


    echo "Готово. Пропишите команду полученя товаров на сервере";
    echo "<br/><a href='/order.html'>Вернуться</a>";

    $webhookurl = "https://discord.com/api/webhooks/848280834872180796/5P5D3Cq477Ojy097N4M8ZQZpxGBwPJgNoz1beVVNvTPcdw5pznVQJfSFP7XMBNHEQFDh";
    $timestamp = date("c", strtotime("now"));
    $json_data = json_encode([
        "content" => "Заказ оплачен",
        "username" => "httpd container",
        "tts" => false,
        "embeds" => [
            [
                "title" => "Заказ: " . $paymentInfo->status . ". От: " . $paymentInfo->date,
                "description" => "Номер заказа: " . $paymentInfo->paymentId . "\n" .
                    "Номер товара: " . $result['goods_id'] . ".\nИгрок: " . $result['nickname'] .
                    "\nТовары: " . $result ["items"],
                "type" => "rich",
                "timestamp" => $timestamp,
                "color" => hexdec("42f55d"),
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

    if ($paymentInfo->status == "success") {
        unset($_SESSION['id']);
    }


} elseif (isset($response->error->message)) {
    $error = $response->error->message;
    print 'Error: ' . $error;
}

//https://web.eternal-server.net/success.php?paymentId=2052311501&account=gladdos
//https://web.eternal-server.net/success.php?paymentId=2044099949&account=1