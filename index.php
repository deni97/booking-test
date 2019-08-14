<?php

use Reservations\Core\Router;
use Reservations\Core\Request;
use Reservations\Core\Config;
use Reservations\Utils\DependencyInjector;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/vendor/autoload.php';

header('Content-Type: text/html; charset=utf-8'); 

$config = new Config();

$dbConfig = $config->get('db');
$db = new PDO(
    'mysql:host=127.0.0.1;dbname=booking;charset=utf8',
    $dbConfig['user'],
    $dbConfig['password']
);
$dbArchive = new PDO(
    'mysql:host=127.0.0.1;dbname=booking_archive;charset=utf8',
    $dbConfig['user'],
    $dbConfig['password']
);

$loader = new Twig_Loader_Filesystem(__DIR__ . '/src/Views');
$view = new Twig_Environment($loader);

$log = new Logger('bookstore');
$logFile = $config->get('log');
$log->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

$di = new DependencyInjector();
$di->set('PDO', $db);
$di->set('Utils\Config', $config);
$di->set('Twig_Environment', $view);
$di->set('Logger', $log);
$di->set('archive', $dbArchive);

$router = new Router($di);
$response = $router->route(new Request());
echo $response;