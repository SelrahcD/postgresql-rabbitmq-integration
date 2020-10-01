<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageBus;
use SelrahcD\PostgresRabbitMq\MessageStorage;
use SelrahcD\PostgresRabbitMq\QueueExchangeManager;
use SelrahcD\PostgresRabbitMq\UserRepository;

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

$container[UserRepository::class] = new UserRepository($container[PDO::class]);
$container[Logger::class] = new Logger(getenv('MESSAGE_LOG_FILE'));
$container[MessageStorage::class] = new MessageStorage($container[PDO::class]);
$container[MessageStorage::class] = new MessageStorage($container[PDO::class]);
$container[AMQPChannel::class] = $container[AMQPStreamConnection::class]->channel();
$container[MessageBus::class] = new MessageBus($container[AMQPChannel::class]);
$container[QueueExchangeManager::class] = new QueueExchangeManager($container[AMQPChannel::class]);

return $container;
