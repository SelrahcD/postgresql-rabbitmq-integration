<?php


use PhpAmqpLib\Channel\AMQPChannel as AMQPChannelAlias;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Process\Process;

class PostgresqlRabbitmqIntegrationTest extends TestCase
{
    private static AMQPChannelAlias $channel;

    private static AMQPStreamConnection $connection;

    private const MESSAGE_LOG_FILE = __DIR__ . '/../var/logs/test/messages.log';
    /**
     * @var Process
     */
    private static Process $process;

    private static PDO $pdo;

    private static UuidInterface $messageId;

    private static string $username;

    public static function setUpBeforeClass(): void
    {
        $container = require __DIR__ . '/../src/container.php';

        static::$connection = $container[AMQPStreamConnection::class]();

        static::$channel = static::$connection->channel();

        static::$channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT);
        static::$channel->queue_declare('incoming_message_queue');
        static::$channel->queue_bind('incoming_message_queue', 'messages_in');


        static::$channel->exchange_declare('messages_out', AMQPExchangeType::DIRECT, false, false, false);
        static::$channel->queue_declare('outgoing_message_queue',false, false, false , false);
        static::$channel->queue_bind('outgoing_message_queue', 'messages_out');

        static::$pdo = $container[PDO::class]();

        static::$pdo->exec("DROP TABLE IF EXISTS received_messages");
        static::$pdo->exec(
            "CREATE TABLE IF NOT EXISTS received_messages (
                         message_id VARCHAR(255)
         )");

        static::$pdo->exec("DROP TABLE IF EXISTS users");
        static::$pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                         username VARCHAR(255)
         )");

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

             $logFile = fopen(self::MESSAGE_LOG_FILE, 'r');

             while (($line = fgets($logFile)) !== false) {
                 if($line == 'received:' . static::$messageId->toString() . PHP_EOL) {
                     $messageReceived = true;
                 }
             }
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

            $sth = static::$pdo->prepare("SELECT count(*) FROM received_messages WHERE message_id = :message_id");
            $sth->bindParam(':message_id', static::$messageId->toString());
            $sth->execute();

            $count = $sth->fetchColumn();

            if($count > 0) {
                $messageReceived = true;
            }
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

             $sth = static::$pdo->prepare("SELECT count(*) FROM users WHERE username = :username");
             $sth->bindParam(':username', static::$username);
             $sth->execute();

             $count = $sth->fetchColumn();

             if($count > 0) {
                 $messageReceived = true;
             }
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
