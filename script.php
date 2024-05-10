<?php
require_once __DIR__ . '/vendor/autoload.php'; // Include Composer's autoloader
include 'conn.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
@error_reporting(E_ALL ^ E_DEPRECATED);
define('AMQP_PROTOCOL', '0.8');

// RabbitMQ configuration
$rabbitmq_host = 'localhost';
$rabbitmq_port = 5672;
$rabbitmq_user = 'guest';
$rabbitmq_pass = 'guest';
$rabbitmq_queue_name = 'image_queue';

$imageDirectory = "storage/images/";

// Create connection to RabbitMQ
$connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_pass);

$channel = $connection->channel();
$channel->queue_declare($rabbitmq_queue_name, false, true, false, false);
//  echo $channel;



function fetchImagesFromAPI($count) {
    $access_key = "FlW8P3iJda2IvGS3hmHzM45Ouzd-QEa547wdeNef9j0";
    $url = "https://api.unsplash.com/photos/random?count=$count&client_id=$access_key";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    return $data;
}



function insertImagesIntoDB($conn, $images) {
    global $imageDirectory;
    foreach ($images as $image) {
        $url = $image['urls']['regular'];
        $description = $image['alt_description'];
        $fileName = uniqid() . '.jpg'; // Generate unique filename


        // Insert image data into the database
        // $sql = "INSERT INTO images(url, alt_description) VALUES('$url', '$description')";
        $sql = "INSERT INTO images(url, alt_description, image_name) VALUES('$url', '$description','$fileName')";


        if ($conn->query($sql) !== TRUE) {
            echo "Error: " . $sql . "<br>" . $conn->error;
        } else {
            // Download and save the image locally
            $imageData = file_get_contents($url);
            $filePath = $imageDirectory . $fileName;
            file_put_contents($filePath, $imageData);
            // echo "Image downloaded: $fileName\n";
        }
    }
}

$callback = function($msg) {
    global $conn;
    $images = json_decode($msg->body, true);
    insertImagesIntoDB($conn, $images);
    echo "Images inserted successfully.\n";
};

// Consume messages from the queue
$channel->basic_consume($rabbitmq_queue_name, '', false, true, false, false, $callback);

$images = fetchImagesFromAPI(100);

// echo "<pre>";
//     print_r($images);
// echo "</pre>";


// Publish messages to the queue
foreach (array_chunk($images, 5) as $batch) {
    $msg = new AMQPMessage(json_encode($batch));
    $channel->basic_publish($msg, '', $rabbitmq_queue_name);
    echo "[x] batch completed! \n";
}

// Wait for messages
while(count($channel->callbacks)) {
    $channel->wait();
}

// Close connections
$channel->close();
$connection->close();

?>
