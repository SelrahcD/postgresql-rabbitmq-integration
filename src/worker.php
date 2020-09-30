<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

require_once __DIR__ . '/../vendor/autoload.php';

$connection = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_DEFAULT_USER'),
    getenv('RABBITMQ_DEFAULT_PASS')
);

$channel = $connection->channel();

$channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT);
$channel->queue_declare('incoming_message_queue');
$channel->queue_bind('incoming_message_queue', 'messages_in');

$logger = new class(getenv('MESSAGE_LOG_FILE')) {

    private string $messageLogFile;

    public function __construct(string $messageLogFile)
    {
        $this->messageLogFile = $messageLogFile;
    }

    public function logMessageReceived(AMQPMessage $message)
    {
        $headers = $message->get_properties();
        $messageId = $headers['message_id'];
        file_put_contents($this->messageLogFile, 'received:' . $messageId . PHP_EOL, FILE_APPEND);
    }
};


$callback = function ($msg) use($logger) {

    $logger->logMessageReceived($msg);

    echo '[x] Received ', $msg->body, "\n";
};

$channel->basic_consume('incoming_message_queue', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}