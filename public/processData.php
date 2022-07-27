<?php declare(strict_types=1);
require "../vendor/autoload.php";

use Restaurants\Utils\FeedObject;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();
$file = fopen($_ENV['INPUT_PATH'], 'rb');
if(!$file) {
    echo "Following file read error happened";
    echo error_get_last();
    die();
}
$feed = new FeedObject($_ENV['OUTPUT_PATH']);
try {
    $header = fgets($file);
    if (str_contains($header, "<?xml")) {
        $feed->setHeader($header);
    }
    $feed->handleLine($header);
    while (($line = fgets($file)) !== false) {
        $feed->handleLine($line);
    }
    $feed->clear();
} catch (DOMException $e) {
    echo "Following DOM Error happened:".PHP_EOL;
    echo $e->getMessage();
}
$feed->printCounts();

