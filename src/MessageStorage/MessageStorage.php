<?php

namespace SelrahcD\PostgresRabbitMq\MessageStorage;

interface MessageStorage
{
    public function recordMessageAsHandled(string $messageId): void;

    public function isAlreadyHandled(string $messageId): bool;
}