<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageBus;
use SelrahcD\PostgresRabbitMq\MessageHandler;
use SelrahcD\PostgresRabbitMq\MessageStorage;
use SelrahcD\PostgresRabbitMq\MessageStorage\GoodMessageStorage;
use SelrahcD\PostgresRabbitMq\MessageStorage\IntermittentFailureMessageStorage;
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

$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$container[PDO::class] = $pdo;

$container[AMQPStreamConnection::class] = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_DEFAULT_USER'),
    getenv('RABBITMQ_DEFAULT_PASS')
);

$userRepositoryClass = getenv('USER_REPOSITORY') !== false ? getenv('USER_REPOSITORY'): GoodUserRepository::class;
$messageStorageClass = getenv('MESSAGE_STORAGE') !== false ? getenv('MESSAGE_STORAGE'): GoodMessageStorage::class;

$container[GoodUserRepository::class] = new GoodUserRepository($container[PDO::class]);
$container[IntermittentFailureUserRepository::class] = new IntermittentFailureUserRepository($container[GoodUserRepository::class]);
$container[UserRepository::class] = $container[$userRepositoryClass];

$container[GoodMessageStorage::class] = new GoodMessageStorage($container[PDO::class]);
$container[IntermittentFailureMessageStorage::class] = new IntermittentFailureMessageStorage($container[GoodMessageStorage::class]);
$container[MessageStorage::class] =  $container[$messageStorageClass];

$container[Logger::class] = new Logger(getenv('MESSAGE_LOG_FILE'));
$container[GoodMessageStorage::class] = new GoodMessageStorage($container[PDO::class]);
$container[AMQPChannel::class] = $container[AMQPStreamConnection::class]->channel();
$container[MessageBus::class] = new MessageBus($container[AMQPChannel::class]);
$container[QueueExchangeManager::class] = new QueueExchangeManager($container[AMQPChannel::class]);
$container[MessageHandler::class] = new MessageHandler($container[MessageBus::class], $container[UserRepository::class]);

return $container;
