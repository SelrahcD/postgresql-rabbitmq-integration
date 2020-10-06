<?php

namespace SelrahcD\PostgresRabbitMq;

interface MessageStorage
{
    public function recordMessageAsHandled(string $messageId): void;

    public function isAlreadyHandled(string $messageId): bool;
}