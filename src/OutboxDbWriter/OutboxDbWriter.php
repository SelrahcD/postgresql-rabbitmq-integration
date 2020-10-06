<?php

namespace SelrahcD\PostgresRabbitMq\OutboxDbWriter;

interface OutboxDbWriter
{
    public function insert(string $messageId, string $body): void;

    public function unsentMessages(): array;

    public function delete(string $messageId): void;
}