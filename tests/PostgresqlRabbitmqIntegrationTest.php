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

class PostgresqlRabbitmqIntegrationTest extends TestCase
{
    private static AMQPChannel $channel;

    private static AMQPStreamConnection $connection;

    private const MESSAGE_LOG_FILE = __DIR__ . '/../var/logs/test/messages.log';
    /**
     * @var Process
     */
    private static Process $process;

    private static PDO $pdo;

    private static UuidInterface $messageId;

    private static string $username;

    private static MessageStorage $messageStorage;

    private static UserRepository $userRepository;

    private static Logger $logger;

    public static function setUpBeforeClass(): void
    {
        $container = require __DIR__ . '/../src/container.php';

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

        static::$username = 'Selrahcd_' . rand(0,1000);

        $messageBody = json_encode([
            'username' => static::$username,
        ]);

        $message = new AMQPMessage($messageBody, ['message_id' => static::$messageId]);

        static::$channel->basic_publish($message, 'messages_in');
    }

    protected function tearDown(): void
    {
        echo static::$process->getOutput();
    }

    public static function tearDownAfterClass(): void
    {
        static::$channel->close();
        static::$connection->close();
        static::$process->stop();
    }


    protected function setUp(): void
    {
        @unlink(self::MESSAGE_LOG_FILE);
        touch(self::MESSAGE_LOG_FILE);
    }

    /**
     * @test
     */
     public function worker_receives_a_message(): void
     {
         $start = time();
         $messageReceived = false;
         while(!$messageReceived) {

             if(time() - $start > 5) {
                 $this->fail('Message wasn\'t received after 5 seconds.');
             }

             $messageReceived = static::$logger->hasReceivedMessageReceivedLogForMessageId(static::$messageId->toString());
         }

         self::assertTrue($messageReceived);
     }

    /**
     * @test
     */
    public function message_is_marked_as_received_in_postgresql(): void
    {
        $start = time();
        $messageReceived = false;
        while(!$messageReceived) {

            if(time() - $start > 5) {
                $this->fail('Message wasn\'t stored in postgresql after 5 seconds.');
            }

            $messageReceived = static::$messageStorage->wasMessageOfIdReceived(static::$messageId->toString());
        }

        self::assertTrue($messageReceived);
    }
    
    /**
     * @test
     */
     public function user_is_stored_in_users_table(): void
     {
         $start = time();
         $messageReceived = false;
         while(!$messageReceived) {

             if(time() - $start > 5) {
                 $this->fail('User wasn\'t stored in users table after 5 seconds');
             }

             $messageReceived = static::$userRepository->isUsernameRegistered(static::$username);
         }

         self::assertTrue($messageReceived);
     }

    /**
     * @test
     */
    public function userRegistered_event_is_published(): void
    {
        $receivedMessages = [];

        $callback = function (AMQPMessage $message) use(&$receivedMessages){
            $receivedMessages[] = $message->body;
        };

        static::$channel->basic_consume('outgoing_message_queue', '', false, true, false, false, $callback);

        $start = time();
        $messageReceived = false;
        $expectedMessage = json_encode(['eventName' => 'UserRegistered', 'username' => self::$username]);
        while(!$messageReceived) {

            static::$channel->wait(null, true);

            if(time() - $start > 5) {
                $this->fail('UserRegistered event wasn\'t received after 5 seconds');
            }

            foreach ($receivedMessages as $receivedMessage) {
                if($receivedMessage === $expectedMessage) {
                    $messageReceived = true;
                }
            }
        }

        self::assertTrue($messageReceived);
    }
}
