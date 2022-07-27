<?php declare(strict_types=1);
require "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();
$file = fopen($_ENV['OUTPUT_PATH'], 'rb');
if(!$file) {
    echo "Following file read error happened";
    echo error_get_last();
    die();
}
$active = 0;
$paused = 0;
while(($line = fgets($file)) !== false) {
    if(str_contains($line, '<is_active><![CDATA[true]]></is_active>')) {
        $active++;
    }
    if(str_contains($line, '<is_active><![CDATA[false]]></is_active>')) {
        $paused++;
    }
}
echo "Active: ".$active.PHP_EOL;
echo "Paused: ".$paused.PHP_EOL;