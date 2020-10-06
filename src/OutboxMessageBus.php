<?php

namespace SelrahcD\PostgresRabbitMq;

use PDO;
use Ramsey\Uuid\Uuid;

class OutboxMessageBus implements MessageBus
{
    private \PDO $pdo;

    private AmqpMessagePublisher $messagePublisher;

    public function __construct(\PDO $pdo, AmqpMessagePublisher$messagePublisher)
    {
        $this->pdo = $pdo;
        $this->messagePublisher = $messagePublisher;
    }

    public function publish(array $message): void
    {
        $sth = $this->pdo->prepare('INSERT INTO messages_outbox (body, message_id) VALUES (:body, :message_id)');
        $messageBody = json_encode($message);
        $sth->bindParam('body', $messageBody);
        $id = Uuid::uuid4()->toString();
        $sth->bindParam('message_id', $id);
        $sth->execute();
    }

    public function sendMessages()
    {
        $sth = $this->pdo->prepare('SELECT message_id, body, message_id FROM messages_outbox WHERE sent = 0');
        $sth->execute();

        $unsentMessages = $sth->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

        $sth = $this->pdo->prepare('UPDATE messages_outbox SET sent = 1 WHERE message_id = :message_id');
        foreach ($unsentMessages as $unsentMessage) {
            $this->messagePublisher->publish($unsentMessage['body'], $unsentMessage['message_id']);
            $sth->bindParam(':message_id', $unsentMessage['message_id']);
            $sth->execute();
        }
    }
}