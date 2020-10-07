<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageHandler;
use SelrahcD\PostgresRabbitMq\MessageStorage\MessageStorage;
use SelrahcD\PostgresRabbitMq\OutboxMessageBus;
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
$outboxMessageBus = $container[OutboxMessageBus::class];

$callback = function (AMQPMessage $message) use($logger, $messageStorage, $messageHandler, $pdo, $outboxMessageBus) {

    $headers = $message->get_properties();
    $messageId = $headers['message_id'];
    $logger->logMessageReceived($messageId);

    try {
        $pdo->beginTransaction();

        try {
            if (!$messageStorage->isAlreadyHandled($messageId)) {
                $messageHandler->handle($message);
                $messageStorage->recordMessageAsHandled($messageId);
            }
        } catch (\Exception $exception) {

            if($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        if($pdo->commit() == false) {
            throw new Exception(PDO_COMMIT_TRANSACTION_FAILURE);
        }


        $outboxMessageBus->sendMessages();

    } catch (\Exception $exception) {
        echo $exception->getMessage();
        $logger->logError($exception->getMessage());
        $logger->logMessageNacked($messageId);
        $message->nack(true);
        return;
    }

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
