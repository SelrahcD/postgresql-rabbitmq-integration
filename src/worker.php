<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageBus;
use SelrahcD\PostgresRabbitMq\MessageStorage;
use SelrahcD\PostgresRabbitMq\QueueExchangeManager;
use SelrahcD\PostgresRabbitMq\UserRepository;
use PhpAmqpLib\Channel\AMQPChannel;

$container = require __DIR__ . '/container.php';

$connection = $container[AMQPStreamConnection::class];
$pdo = $container[PDO::class];
$messageStorage = $container[MessageStorage::class];
$userRepository = $container[UserRepository::class];
$logger = $container[Logger::class];
$messageBus = $container[MessageBus::class];
$channel = $container[AMQPChannel::class];
$container[QueueExchangeManager::class]->setupQueues();

$callback = function (AMQPMessage $message) use($logger, $messageStorage, $userRepository, $messageBus) {

    echo '[x] Received ', $message->body, "\n";

    $data = json_decode($message->body, true);
    $username = $data['username'];

    $logger->logMessageReceived($message);
    $messageStorage->storeMessageWasReceived($message);
    $userRepository->registerUser($username);

    $messageBus->publish([
        'eventName' => 'UserRegistered',
        'username' => $username
    ]);
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
