<?php
require_once __DIR__ . '/vendor/autoload.php'; // Include Composer's autoloader
// include 'conn.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
// header("Refresh:3");

@error_reporting(E_ALL ^ E_DEPRECATED);


$rabbitmq_host = 'localhost';
$rabbitmq_port = 5672;
$rabbitmq_user = 'guest';
$rabbitmq_pass = 'guest';
$rabbitmq_queue_name = 'test_queue';

// Create connection to RabbitMQ
$connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_pass);
$channel = $connection->channel();
$channel->queue_declare($rabbitmq_queue_name, false, true, false, false);

for ($i=1; $i <=5; $i++) { 

    $msg =  new AMQPMessage('Rec message no:  '.$i);
    $channel->basic_publish($msg, '', $rabbitmq_queue_name);
}

echo "[x] Message Sent! \n";
echo date("Y-m-d h:i:s a");

$channel->close();
$connection->close();


?>