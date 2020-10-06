<?php


namespace SelrahcD\PostgresRabbitMq;


interface OutboxMessageBusDbWriter
{
    public function insert(string $messageId, string $body): void;

    public function unsentMessages(): array;

    public function delete(string $messageId): void;
}