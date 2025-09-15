<?php 

require_once __DIR__ . '/vendor/autoload.php';

use  Vantage\Crawler;

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

$number = (int)$input;
if ($topics[$number]) {
    ($topics[$number])->fetchResults();
}

