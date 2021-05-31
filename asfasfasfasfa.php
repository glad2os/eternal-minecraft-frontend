<?php
require_once(__DIR__ . "/vendor/autoload.php");
session_start();
var_dump($_SESSION['id']);
die;
$host = 'localhost'; // Server host name or IP
$port = 25575;                      // Port rcon is listening on
$password = '2zSHzLPfx3Mq'; // rcon.password setting set in server.properties
$timeout = 3;                       // How long to timeout.

use Thedudeguy\Rcon;

$rcon = new Rcon($host, $port, $password, $timeout);

if ($rcon->connect())
{
  $rcon->sendCommand("say Hello World!");
}