<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher;
use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher\IntermittentAmqpMessagePublisher;
use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher\GoodAmqpMessagePublisher;
use SelrahcD\PostgresRabbitMq\GoodOutboxBusDbWriter;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageBus;
use SelrahcD\PostgresRabbitMq\MessageHandler;
use SelrahcD\PostgresRabbitMq\MessageStorage;
use SelrahcD\PostgresRabbitMq\MessageStorage\GoodMessageStorage;
use SelrahcD\PostgresRabbitMq\MessageStorage\IntermittentFailureMessageStorage;
use SelrahcD\PostgresRabbitMq\OutboxMessageBus;
use SelrahcD\PostgresRabbitMq\OutboxMessageBusDbWriter;
use SelrahcD\PostgresRabbitMq\PDOWrapper;
use SelrahcD\PostgresRabbitMq\QueueExchangeManager;
use SelrahcD\PostgresRabbitMq\UserRepository\GoodUserRepository;
use SelrahcD\PostgresRabbitMq\UserRepository;
use SelrahcD\PostgresRabbitMq\UserRepository\IntermittentFailureUserRepository;

require_once __DIR__ . '/../vendor/autoload.php';

$container = [];

$postgresHost = getenv('POSTGRES_HOST');
$postgresDB = getenv('POSTGRES_DB');
$postgresUsername = getenv('POSTGRES_USER');
$postgresPassword = getenv('POSTGRES_PASSWORD');

$dsn = "pgsql:host=$postgresHost;port=5432;dbname=$postgresDB;user=$postgresUsername;password=$postgresPassword";
$container[Logger::class] = new Logger(getenv('MESSAGE_LOG_FILE'));

$pdo = new PDOWrapper($dsn,
    getEnvOrDefault('PDO_START_TRANSACTION_FAILURE', 0),
    getEnvOrDefault('PDO_COMMIT_TRANSACTION_FAILURE', 0)
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$container[PDO::class] = $pdo;

$container[AMQPStreamConnection::class] = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_DEFAULT_USER'),
    getenv('RABBITMQ_DEFAULT_PASS')
);

$container[GoodUserRepository::class] = new GoodUserRepository($container[PDO::class]);

$container[UserRepository::class] = new IntermittentFailureUserRepository(
    $container[GoodUserRepository::class],
    getEnvOrDefault('USER_REPOSITORY_REGISTRATION_FAILURE', 0)
);

$container[MessageStorage::class] = new IntermittentFailureMessageStorage(
    new GoodMessageStorage($container[PDO::class]),
    getEnvOrDefault('MESSAGE_STORAGE_WRITE_FAILURE', 0),
    getEnvOrDefault('MESSAGE_STORAGE_READ_FAILURE', 0)
);

$container[AMQPChannel::class] = $container[AMQPStreamConnection::class]->channel();
$container[GoodAmqpMessagePublisher::class] = new GoodAmqpMessagePublisher($container[AMQPChannel::class]);
$container[AmqpMessagePublisher::class] = new IntermittentAmqpMessagePublisher(
    $container[GoodAmqpMessagePublisher::class],
    getEnvOrDefault('AMQP_MESSAGE_PUBLISH_FAILURES', 0)
);

$container[OutboxMessageBusDbWriter::class] = new IntermittentOutboxDbWriter(
    new GoodOutboxBusDbWriter($container[PDO::class]),
    getEnvOrDefault('OUTBOX_DB_WRITER_INSERT_FAILURE', 0),
    getEnvOrDefault('OUTBOX_DB_WRITER_READ_FAILURE', 0),
    getEnvOrDefault('OUTBOX_DB_WRITER_DELETE_FAILURE', 0)
);

$container[OutboxMessageBus::class] = new OutboxMessageBus($container[OutboxMessageBusDbWriter::class], $container[AmqpMessagePublisher::class]);
$container[MessageBus::class] = $container[OutboxMessageBus::class];

$container[QueueExchangeManager::class] = new QueueExchangeManager($container[AMQPChannel::class]);
$container[MessageHandler::class] = new MessageHandler($container[MessageBus::class], $container[UserRepository::class]);

return $container;
