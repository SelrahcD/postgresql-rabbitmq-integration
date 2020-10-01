<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageStorage;
use SelrahcD\PostgresRabbitMq\QueueExchangeManager;
use SelrahcD\PostgresRabbitMq\UserRepository;

$container = require __DIR__ . '/container.php';

$connection = $container[AMQPStreamConnection::class];
$pdo = $container[PDO::class];
$messageStorage = $container[MessageStorage::class];
$userRepository = $container[UserRepository::class];
$logger = $container[Logger::class];

$channel = $connection->channel();
QueueExchangeManager::setupQueues($channel);

$callback = function (AMQPMessage $message) use($logger, $messageStorage, $userRepository, $channel) {

    echo '[x] Received ', $message->body, "\n";

    $data = json_decode($message->body, true);
    $username = $data['username'];

    $logger->logMessageReceived($message);
    $messageStorage->storeMessageWasReceived($message);
    $userRepository->registerUser($username);


    $event = new AMQPMessage(json_encode([
        'eventName' => 'UserRegistered',
        'username' => $username
    ]));

    $channel->basic_publish($event, 'messages_out');
};

$channel->basic_consume('incoming_message_queue', '', false, true, false, false, $callback);


function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while ($channel->is_consuming()) {
    $channel->wait();
}
