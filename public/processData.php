<?php declare(strict_types=1);

use Restaurants\Utils\FeedObject;

require "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();
$file = fopen($_ENV['INPUT_PATH'], 'rb');
if(!$file) {
    echo error_get_last();
    die();
}
$feed = new FeedObject($_ENV['OUTPUT_PATH']);
$header = fgets($file);
if(str_contains($header,"<?xml")) {
    $feed->setHeader($header);
}
$feed->handleLine($header);
while(($line = fgets($file)) !== false) {
    $feed->handleLine($line);
}
$feed->clear();
$feed->printCounts();

