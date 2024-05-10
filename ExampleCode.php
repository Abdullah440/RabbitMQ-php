<?php

require_once __DIR__ . '/vendor/autoload.php'; // Include Composer's autoloader

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Database configuration
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "dbname";

// RabbitMQ configuration
$rabbitmq_host = 'localhost';
$rabbitmq_port = 5672;
$rabbitmq_user = 'guest';
$rabbitmq_pass = 'guest';
$rabbitmq_queue_name = 'image_queue';

// Create connection to RabbitMQ
$connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_pass);
$channel = $connection->channel();

// Declare the queue
$channel->queue_declare($rabbitmq_queue_name, false, true, false, false);

// Function to fetch images from the API
function fetchImagesFromAPI($count = 10) {
    $access_key = "your_access_key"; // Replace with your Unsplash access key
    $url = "https://api.unsplash.com/photos/random?count=$count&client_id=$access_key";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    return $data;
}

// Function to insert images into the database
function insertImagesIntoDB($conn, $images) {
    foreach ($images as $image) {
        $url = $image['urls']['regular'];
        $description = $image['alt_description'];

        // Insert image data into the database
        $sql = "INSERT INTO images (url, description) VALUES ('$url', '$description')";

        if ($conn->query($sql) !== TRUE) {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Callback function to handle messages from the queue
$callback = function($msg) {
    global $conn;
    $images = json_decode($msg->body, true);
    insertImagesIntoDB($conn, $images);
    echo "Images inserted successfully.\n";
};

// Consume messages from the queue
$channel->basic_consume($rabbitmq_queue_name, '', false, true, false, false, $callback);

// Fetch images from the API
$images = fetchImagesFromAPI();

// Publish messages to the queue
foreach (array_chunk($images, 5) as $batch) {
    $msg = new AMQPMessage(json_encode($batch));
    $channel->basic_publish($msg, '', $rabbitmq_queue_name);
}

// Wait for messages
while(count($channel->callbacks)) {
    $channel->wait();
}

// Close connections
$channel->close();
$connection->close();

?>
