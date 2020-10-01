<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

require_once __DIR__ . '/../vendor/autoload.php';

$connection = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_DEFAULT_USER'),
    getenv('RABBITMQ_DEFAULT_PASS')
);

$channel = $connection->channel();

$channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT);
$channel->queue_declare('incoming_message_queue');
$channel->queue_bind('incoming_message_queue', 'messages_in');

$channel->exchange_declare('messages_out', AMQPExchangeType::DIRECT, false, false, false);
$channel->queue_declare('outgoing_message_queue',false, false, false , false);
$channel->queue_bind('outgoing_message_queue', 'messages_out');

$postgresHost = getenv('POSTGRES_HOST');
$postgresDB = getenv('POSTGRES_DB');
$postgresUsername = getenv('POSTGRES_USER');
$postgresPassword = getenv('POSTGRES_PASSWORD');

$dsn = "pgsql:host=$postgresHost;port=5432;dbname=$postgresDB;user=$postgresUsername;password=$postgresPassword";

$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$messageStorage = new class($pdo) {
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function storeMessageWasReceived(AMQPMessage $message): void
    {
        $headers = $message->get_properties();
        $messageId = $headers['message_id'];

        $sth = $this->pdo->prepare('INSERT INTO received_messages (message_id) VALUES (:message_id)');
        $sth->bindParam('message_id', $messageId);
        $sth->execute();
    }
};

$userRepository = new class($pdo) {
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registerUser(string $username): void
    {
        $sth = $this->pdo->prepare('INSERT INTO users (username) VALUES (:username)');
        $sth->bindParam('username', $username);
        $sth->execute();
    }
};

$logger = new class(getenv('MESSAGE_LOG_FILE')) {

    private string $messageLogFile;

    public function __construct(string $messageLogFile)
    {
        $this->messageLogFile = $messageLogFile;
    }

    public function logMessageReceived(AMQPMessage $message)
    {
        $headers = $message->get_properties();
        $messageId = $headers['message_id'];
        file_put_contents($this->messageLogFile, 'received:' . $messageId . PHP_EOL, FILE_APPEND);
    }
};

$callback = function (AMQPMessage $message) use($logger, $messageStorage, $userRepository, $channel) {

    echo '[x] Received ', $message->body, "\n";

    $data = json_decode($message->body, true);
    $username = $data['username'];

    $logger->logMessageReceived($message);
    $messageStorage->storeMessageWasReceived($message);
    $userRepository->registerUser($username);


    $event = new AMQPMessage(json_encode([
        'eventName' => 'UserRegistered',
        'username' => $username
    ]));

    $channel->basic_publish($event, 'messages_out');
};

$channel->basic_consume('incoming_message_queue', '', false, true, false, false, $callback);


function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while ($channel->is_consuming()) {
    $channel->wait();
}
