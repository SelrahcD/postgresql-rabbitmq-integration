<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SelrahcD\PostgresRabbitMq\FixtureManagers;
use SelrahcD\PostgresRabbitMq\Logger;
use SelrahcD\PostgresRabbitMq\MessageStorage\GoodMessageStorage;
use SelrahcD\PostgresRabbitMq\QueueExchangeManager;
use SelrahcD\PostgresRabbitMq\UserRepository\GoodUserRepository;
use Symfony\Component\Process\Process;

abstract class PostgresqlRabbitmqIntegrationTest extends TestCase
{
    protected AMQPChannel $channel;

    protected AMQPStreamConnection $connection;

    protected const MESSAGE_LOG_FILE = __DIR__ . '/../var/logs/test/messages.log';
    /**
     * @var Process
     */
    protected Process $process;

    protected PDO $pdo;

    protected string $messageId;

    protected string $username;

    protected GoodMessageStorage $messageStorage;

    protected GoodUserRepository $userRepository;

    protected Logger $logger;

    protected function setUp(): void
    {
        $container = require __DIR__ . '/../src/container.php';

        @unlink(self::MESSAGE_LOG_FILE);
        touch(self::MESSAGE_LOG_FILE);

        $this->connection = $container[AMQPStreamConnection::class];

        $this->channel = $this->connection->channel();

        $this->pdo = $container[PDO::class];

        $this->messageStorage = $container[GoodMessageStorage::class];

        $this->userRepository = $container[GoodUserRepository::class];

        $this->logger = new Logger(static::MESSAGE_LOG_FILE);

        $container[QueueExchangeManager::class]->setupQueues('outgoing_message_queue');

        $this->channel->queue_purge('outgoing_message_queue');

        FixtureManagers::setupFixtures($this->pdo);

        $this->process = new Process(
            ['php', './src/worker.php'],
            __DIR__ . '/..',
            array_merge([
                'MESSAGE_LOG_FILE' => self::MESSAGE_LOG_FILE
            ], $this->implementations())
        );

        $this->process->start();

        $this->messageId = Uuid::uuid4()->toString();

        $this->username = 'Selrahcd_' . rand(0, 1000);

        $messages = $this->messagesToSend();

        foreach ($messages as $message) {
            $this->channel->basic_publish($message, 'messages_in');
        }

        $start = time();
        $messageWasAcked = false;
        $messagesWereHandled = false;
        while (!$messageWasAcked && !$messagesWereHandled) {

            if (time() - $start > 5) {
                $this->fail('Message wasn\'t acked after 5 seconds');
            }

            $messageWasAcked = $this->logger->hasBeenAcked($this->messageId);
            $messagesWereHandled = $this->logger->hasHandledMessageAtLeast($this->messageId, count($messages));
        }
    }

    protected function tearDown(): void
    {
        $this->channel->close();
        $this->connection->close();
        $this->process->stop();

        echo $this->process->getOutput();
    }

    /**
     * @test
     */
    public function user_is_stored_in_users_table_only_once(): void
    {
        self::assertEquals(1, $this->userRepository->countOfUserRegisteredWith($this->username));
    }

    /**
     * @test
     */
    public function all_dispatched_messages_have_the_same_message_id(): void
    {
        $receivedMessageIds = [];

        $callback = function (AMQPMessage $message) use (&$receivedMessageIds) {
            $headers = $message->get_properties();
            $messageId = $headers['message_id'];

            $receivedMessageIds[] = $messageId;
        };

        $this->channel->basic_consume('outgoing_message_queue', '', false, true, false, false, $callback);

        $start = time();
        $keepWaiting = true;
        while ($this->channel->is_consuming() && $keepWaiting && count($receivedMessageIds) < 2) {
            $this->channel->wait(null, true);
            $keepWaiting = time() - $start < 2;
        }

        $uniqueReceivedMessageIds = array_unique($receivedMessageIds);

        self::assertCount(1, $uniqueReceivedMessageIds);
    }

    /**
     * @test
     */
    public function userRegistered_event_is_dispatched(): void
    {
        $receivedMessages = [];

        $callback = function (AMQPMessage $message) use (&$receivedMessages) {
            $receivedMessages[] = $message->body;
        };

        $this->channel->basic_consume('outgoing_message_queue', '', false, true, false, false, $callback);

        $start = time();
        $messageReceived = false;
        $expectedMessage = json_encode(['eventName' => 'UserRegistered', 'username' => $this->username]);
        while (!$messageReceived) {

            $this->channel->wait(null, true);
            if (time() - $start > 5) {
                $this->fail('UserRegistered event wasn\'t received after 5 seconds');
            }
            foreach ($receivedMessages as $receivedMessage) {
                if ($receivedMessage === $expectedMessage) {
                    $messageReceived = true;
                }
            }
        }
        self::assertTrue($messageReceived);
    }

    protected function buildCreateUserMessage()
    {
        $messageBody = json_encode([
            'username' => $this->username,
        ]);

        return new AMQPMessage($messageBody, ['message_id' => $this->messageId]);
    }

    protected function messagesToSend(): array
    {
        return [$this->buildCreateUserMessage()];
    }

    protected function implementations()
    {
        return [];
    }
}
