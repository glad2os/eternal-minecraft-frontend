<?php

if (!isset($_GET['nickname'])) die("Никнейм не задан");
if (empty($_GET['nickname'])) die("Никнейм не задан");

include_once __DIR__ . "/../vendor/autoload.php";

session_start();

$prices = json_decode(file_get_contents(__DIR__ . "/../prices.json"), true);

$objectCookie = get_object_vars((object)$_COOKIE);
$cookieNames = array_keys($objectCookie);
$pricesNames = array_keys($prices);

if (count($cookieNames) == 1 && $cookieNames[0] == "PHPSESSID") {
    header("Location: " . "/order.html");
    die;
}


$goods_array = [];

foreach ($cookieNames as $item) {
    $flag = array_search($item, $pricesNames);
    if ($flag !== false) {
        $options = [];
        array_push($options, $item);
        $amountPriceArray = explode("_", $objectCookie[$item]);
        $amount = $amountPriceArray[0];

        if ($amount % $prices[$item]['amount'] == 0) {
            array_push($options, $amount);
            array_push($options, $prices[$item]['price']);
            array_push($goods_array, $options);
        }
    }
}

if (count($goods_array) == 0) {
    header("Location: " . "/order.html");
    die;
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$domain = 'unitpay.ru';
$secretKey = $_ENV['SECRET_KEY'];
$publicId = $_ENV['PUBLIC_KEY'];

$itemName = "";
$overallPrice = 0;

$jsonForDb = [];

$unitPay = new UnitPay($domain, $secretKey);
$unitPay
    ->setBackUrl('https://web.eternal-server.net');

foreach ($goods_array as $item) {
    $itemName .= "Предмет: " . $item[0] . ", кол-во: " . $item[1] . " ";
    $overallPrice += $item[2];
    $unitPay->setCashItems(array(
        new CashItem($item[0], $item[1], (string)$item[2])
    ));
    array_push($jsonForDb, [$item[0], $item[1]]);
}

$itemName .= ". Ваш никнейм: " . $_GET['nickname'];
$orderId = $_GET['nickname'];
$orderDesc = '' . $itemName . '"';
$orderCurrency = 'RUB';

$redirectUrl = $unitPay->form(
    $publicId,
    $overallPrice,
    $orderId,
    $orderDesc,
    $orderCurrency
);

$json = json_encode($jsonForDb);
$mysqli = new mysqli("172.18.0.2", 'root', 'kaTei2ei', 'eternal-project');

if (!isset($_SESSION['id'])) {

    $stmt = $mysqli->prepare("DELETE FROM `goods` WHERE nickname = ? and time < (NOW() - INTERVAL 1 DAY) and given = 0");
    $stmt->bind_param("s", $_GET['nickname']);
    $stmt->execute();


    $stmt2 = $mysqli->prepare("INSERT INTO `goods`(`nickname`, `items`, `given`) VALUES (?,?,false)");
    $stmt2->bind_param("ss", $_GET['nickname'], $json);
    $stmt2->execute();
    $inserted_id = mysqli_stmt_insert_id($stmt2);
    $_SESSION['id'] = $inserted_id;


} else {

    $sql = "SELECT count(*) as count FROM `goods` WHERE `id`=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();

    $result = $stmt->get_result();
    $count = $result->fetch_assoc();

    if ($count['count'] == 0) {
        $stmt2 = $mysqli->prepare("INSERT INTO `goods`(`nickname`, `items`, `given`) VALUES (?,?,false)");
        $stmt2->bind_param("ss", $_GET['nickname'], $json);
        $stmt2->execute();
        $inserted_id = mysqli_stmt_insert_id($stmt2);
        $_SESSION['id'] = $inserted_id;
    } else {
        $stmt2 = $mysqli->prepare("UPDATE `goods` SET `nickname`=?,`items`=?,`given`='false' WHERE id=?");

        $stmt2->bind_param("sss", $_GET['nickname'], $json, $_SESSION['id']);
        $stmt2->execute();
    }
}

$webhookurl = "https://discord.com/api/webhooks/848280834872180796/5P5D3Cq477Ojy097N4M8ZQZpxGBwPJgNoz1beVVNvTPcdw5pznVQJfSFP7XMBNHEQFDh";
$timestamp = date("c", strtotime("now"));
$json_data = json_encode([
    "content" => "Заказ сформирован: " . $json,
    "username" => "httpd container",
    "tts" => false,
    "embeds" => [
        [
            "title" => "Новый заказ",
            "description" => "От: " . $_GET['nickname'] . ". Номер в системе: " . $_SESSION['id'],
            "type" => "rich",
            "timestamp" => $timestamp,
            "color" => hexdec("f5bc42"),
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

header("Location: " . $redirectUrl);