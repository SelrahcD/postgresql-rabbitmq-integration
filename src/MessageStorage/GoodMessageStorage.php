<?php

namespace SelrahcD\PostgresRabbitMq\MessageStorage;

use PDO;

class GoodMessageStorage implements MessageStorage
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function recordMessageAsHandled(string $messageId): void
    {
        $sth = $this->pdo->prepare('INSERT INTO received_messages (message_id) VALUES (:message_id)');
        $sth->bindParam('message_id', $messageId);
        $sth->execute();
    }

    public function isAlreadyHandled(string $messageId): bool
    {
        $sth = $this->pdo->prepare("SELECT count(*) FROM received_messages WHERE message_id = :message_id");
        $sth->bindParam(':message_id', $messageId);
        $sth->execute();

        $count = $sth->fetchColumn();

        return $count > 0;
    }
}