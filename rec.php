<?php
require_once __DIR__ . '/vendor/autoload.php'; // Include Composer's autoloader
@error_reporting(E_ALL ^ E_DEPRECATED);

// include 'conn.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
$rabbitmq_host = 'localhost';
$rabbitmq_port = 5672;
$rabbitmq_user = 'guest';
$rabbitmq_pass = 'guest';
$rabbitmq_queue_name = 'test_queue';

// Create connection to RabbitMQ
$connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_pass);
$channel = $connection->channel();

    echo "[*] Waiting for message. To exit press CTRL + C \n ";
    echo "[*] QUEUE NAME $rabbitmq_queue_name  \n ";
    $callback = function($msg){
        echo "[x]". $msg->body. "\n";
    };

    $channel->basic_consume($rabbitmq_queue_name, '', false, true, false, false, $callback);
    while(count($channel->callbacks)){
        $channel->wait();
    }
    $channel->close();
    $connection->close();
?>