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

    public static function setUpBeforeClass(): void
    {
        static::$connection = new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_DEFAULT_USER'),
            getenv('RABBITMQ_DEFAULT_PASS')
        );

        static::$channel = static::$connection->channel();

        static::$channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT);
        static::$channel->queue_declare('incoming_message_queue');
        static::$channel->queue_bind('incoming_message_queue', 'messages_in');

        $postgresHost = getenv('POSTGRES_HOST');
        $postgresDB = getenv('POSTGRES_DB');
        $postgresUsername = getenv('POSTGRES_USER');
        $postgresPassword = getenv('POSTGRES_PASSWORD');

        $dsn = "pgsql:host=$postgresHost;port=5432;dbname=$postgresDB;user=$postgresUsername;password=$postgresPassword";

        static::$pdo = new PDO($dsn);
        static::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        static::$pdo->exec("DROP TABLE IF EXISTS received_messages");
        static::$pdo->exec(
            "CREATE TABLE IF NOT EXISTS received_messages (
                         message_id VARCHAR(255)
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

        $messageBody = json_encode([
            'username' => 'Charles'
        ]);

        $message = new AMQPMessage($messageBody, ['message_id' => static::$messageId]);

        echo "Publish message";
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

             if(time() - $start > 10) {
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

            if(time() - $start > 10) {
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
}
