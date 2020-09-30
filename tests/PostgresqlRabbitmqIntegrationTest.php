<?php


use PhpAmqpLib\Channel\AMQPChannel as AMQPChannelAlias;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

class PostgresqlRabbitmqIntegrationTest extends TestCase
{
    private AMQPChannelAlias $channel;

    private AMQPStreamConnection $connection;

    private const MESSAGE_LOG_FILE = __DIR__ . '/../var/messages.log';
    /**
     * @var Process
     */
    private Process $process;

    protected function setUp(): void
    {
        unlink(self::MESSAGE_LOG_FILE);
        touch(self::MESSAGE_LOG_FILE);

        $this->connection = new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_DEFAULT_USER'),
            getenv('RABBITMQ_DEFAULT_PASS')
        );

        $this->channel = $this->connection->channel();

        $this->channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT);

        $this->process = new Process(
            ['php', './src/worker.php'],
            __DIR__ . '/..',
            [
                'MESSAGE_LOG_FILE' => self::MESSAGE_LOG_FILE
            ]
        );

        $this->process->start();
    }

    protected function tearDown(): void
    {
        $this->channel->close();
        $this->connection->close();
        $this->process->stop();
    }

    /**
     * @test
     */
     public function worker_receives_a_message(): void
     {
         $messageId = Uuid::uuid4();

         $message = new AMQPMessage('Some message', ['message_id' => $messageId]);

         $this->channel->basic_publish($message, 'messages_in');

         $start = time();
         $messageReceived = false;
         while(!$messageReceived) {

             if(time() - $start > 5) {
                 $this->fail('Message wasn\'t received after 5 seconds.');
             }

             $logFile = fopen(__DIR__ . '/../var/messages.log', 'r');

             while (($line = fgets($logFile)) !== false) {
                 if($line == 'received:' . $messageId->toString() . PHP_EOL) {
                     $messageReceived = true;
                 }
             }
         }

         self::assertTrue($messageReceived);
     }
}
