<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/Queue.php';

$queue = new Queue();
echo "Testing generateQueueNumber(1):\n";
$nextNum = $queue->generateQueueNumber(1);
echo "Result nextNum: " . $nextNum . "\n";

echo "Testing create queue:\n";
try {
    $id = $queue->create([
        'user_id' => 3,
        'restaurant_id' => 1,
        'customer_name' => 'Test User',
        'customer_phone' => '081299998888',
        'people_count' => 2,
        'type' => 'dine_in'
    ]);
    echo "SUCCESS! Created queue with ID: " . $id . "\n";
    $created = $queue->getById($id);
    echo "Created Queue Number: " . $created['queue_number'] . "\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

