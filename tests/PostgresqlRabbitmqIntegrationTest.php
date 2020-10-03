<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SelrahcD\PostgresRabbitMq\FixtureManagers;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageStorage;
use SelrahcD\PostgresRabbitMq\QueueExchangeManager;
use SelrahcD\PostgresRabbitMq\UserRepository;
use Symfony\Component\Process\Process;

abstract class PostgresqlRabbitmqIntegrationTest extends TestCase
{
    protected static AMQPChannel $channel;

    protected static AMQPStreamConnection $connection;

    protected const MESSAGE_LOG_FILE = __DIR__ . '/../var/logs/test/messages.log';
    /**
     * @var Process
     */
    protected static Process $process;

    protected static PDO $pdo;

    protected static UuidInterface $messageId;

    protected static string $username;

    protected static MessageStorage $messageStorage;

    protected static UserRepository $userRepository;

    protected static Logger $logger;

    public static function setUpBeforeClass(): void
    {
        $container = require __DIR__ . '/../src/container.php';

        @unlink(self::MESSAGE_LOG_FILE);
        touch(self::MESSAGE_LOG_FILE);

        static::$connection = $container[AMQPStreamConnection::class];

        static::$channel = static::$connection->channel();

        static::$pdo = $container[PDO::class];

        static::$messageStorage = $container[MessageStorage::class];

        static::$userRepository = $container[UserRepository::class];

        static::$logger = new Logger(static::MESSAGE_LOG_FILE);

        $container[QueueExchangeManager::class]->setupQueues();

        FixtureManagers::setupFixtures(static::$pdo);

        static::$process = new Process(
            ['php', './src/worker.php'],
            __DIR__ . '/..',
            [
                'MESSAGE_LOG_FILE' => self::MESSAGE_LOG_FILE
            ]
        );

        static::$process->start();

        static::$messageId = Uuid::uuid4();

        static::$username = 'Selrahcd_' . rand(0, 1000);

        self::sendMessage();
    }

    protected static function sendMessage(): void
    {
        $messageBody = json_encode([
            'username' => static::$username,
        ]);

        $message = new AMQPMessage($messageBody, ['message_id' => static::$messageId]);

        static::$channel->basic_publish($message, 'messages_in');
    }

    public static function tearDownAfterClass(): void
    {
        static::$channel->close();
        static::$connection->close();
        static::$process->stop();

        echo static::$process->getOutput();
    }

}
