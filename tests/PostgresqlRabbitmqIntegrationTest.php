<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use SelrahcD\PostgresRabbitMq\FixtureManagers;
use SelrahcD\PostgresRabbitMq\Logger;
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
        $messagesWereAcked = false;
        while (!$messagesWereAcked) {

            if (time() - $start > 5) {
                $this->fail('Messages weren\'t acked after 5 seconds');
            }

            $messagesWereAcked = $this->logger->hasBeenAckedAtLeast($this->messageId, count($messages));
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
         $dispatchedMessages = $this->dispatchedMessages();

         $dispatchMessageIds = array_map(function(AMQPMessage $message) {
             $headers = $message->get_properties();
             return $headers['message_id'];
         }, $dispatchedMessages);

         $uniqueDispatchMessageIds = array_unique($dispatchMessageIds);

         self::assertCount(1, $uniqueDispatchMessageIds);
     }

    /**
     * @test
     */
    public function userRegistered_event_is_dispatched(): void
    {
        $dispatchedMessages = $this->dispatchedMessages();

        $expectedMessage = json_encode(['eventName' => 'UserRegistered', 'username' => $this->username]);
        $found = false;
        foreach ($dispatchedMessages as $dispatchedMessage) {
            if($dispatchedMessage->body === $expectedMessage) {
                $found = true;
            }
        }

        self::assertTrue($found, 'UserRegistered event wasn\'t dispatched');
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

    private function dispatchedMessages(): array {

        $receivedMessages = [];

        $callback = function (AMQPMessage $message) use (&$receivedMessages) {
            $receivedMessages[] = $message;
        };

        list(, $messageCount) = $this->channel->queue_declare('outgoing_message_queue', true);
        $this->channel->basic_consume('outgoing_message_queue', '', false, true, false, false, $callback);

        $keepWaiting = true;
        while ($keepWaiting) {
            $this->channel->wait(null, true);
            $keepWaiting = $messageCount > count($receivedMessages);
        }

        return $receivedMessages;
    }
}
