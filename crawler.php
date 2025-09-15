<?php
require_once __DIR__ . '/vendor/autoload.php';
use  Vantage\Crawler;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);


$crawler = Crawler::getInstance();
$topics = $crawler->getTopics();

echo "==============================\n";
echo " Welcome to the Cochrane Crawler\n";
echo "==============================\n\n";
echo "Select a topic to fetch reviews:\n\n";

foreach ($topics as $index => $topic) {
    echo $index . ". " . $topic->getName() . "\n";
}

echo "\nEnter the number of your choice: ";

$input = trim(fgets(STDIN));

if (!is_numeric($input) || !isset($topics[$input])) {
    echo "Invalid choice. Please run the program again.\n";
    exit(1);
}

echo "\nFetching results for '{$topics[$input]->getName()}'...\n";
$topics[$input]->setResults();

echo count($topics[$input]->getResults()) .  " reviews have been found.\n";

echo "Enter a filename to save the results (e.g. cochrane_reviews.txt): \n";
$fileName = trim(fgets(STDIN));

if (empty($fileName)) {
    $fileName = 'cochrane_reviews.txt';
}

$fp = fopen($fileName, 'w');

foreach ($topics[$input]->getResults() as $r) {
    fwrite($fp, (string)$r . PHP_EOL . PHP_EOL);
}

