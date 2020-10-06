<?php
namespace SelrahcD\PostgresRabbitMq;

use PDO;

class GoodOutboxBusDbWriter implements OutboxMessageBusDbWriter
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert(string $messageId, string $body): void
    {
        $sth = $this->pdo->prepare('INSERT INTO messages_outbox (body, message_id) VALUES (:body, :message_id)');
        $sth->bindParam('body', $body);
        $sth->bindParam('message_id', $messageId);
        $sth->execute();
    }

    public function delete(string $messageId): void
    {
        $sth = $this->pdo->prepare('DELETE FROM messages_outbox WHERE message_id = :message_id');
        $sth->bindParam(':message_id', $messageId);
        $sth->execute();
    }

    public function unsentMessages(): array
    {
        $sth = $this->pdo->prepare('SELECT message_id, body, message_id FROM messages_outbox');
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    }
}