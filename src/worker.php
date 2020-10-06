<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageHandler;
use SelrahcD\PostgresRabbitMq\MessageStorage;
use SelrahcD\PostgresRabbitMq\QueueExchangeManager;
use PhpAmqpLib\Channel\AMQPChannel;

$container = require __DIR__ . '/container.php';

$connection = $container[AMQPStreamConnection::class];
$messageStorage = $container[MessageStorage::class];
$logger = $container[Logger::class];
/**
 * @var PDO $pdo
 */
$pdo = $container[PDO::class];
/**
 * @var AMQPChannel $channel
 */
$channel = $container[AMQPChannel::class];
$messageHandler = $container[MessageHandler::class];
$container[QueueExchangeManager::class]->setupQueues();

$callback = function (AMQPMessage $message) use($logger, $messageStorage, $messageHandler, $pdo) {

    $headers = $message->get_properties();
    $messageId = $headers['message_id'];
    $logger->logMessageReceived($messageId);

    $pdo->beginTransaction();

    if(!$messageStorage->isAlreadyHandled($messageId)) {
        try {
            $messageHandler->handle($message);
            $messageStorage->recordMessageAsHandled($messageId);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $pdo->rollBack();
            $message->nack(true);
            $logger->logMessageNacked($messageId);
            return;
        }

    }

    $pdo->commit();

    $logger->logMessageHandled($messageId);

    $message->ack();
    $logger->logMessageAcked($messageId);
};

$channel->basic_consume('incoming_message_queue', '', false, false, false, false, $callback);


function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while ($channel->is_consuming()) {
    $channel->wait();
}
