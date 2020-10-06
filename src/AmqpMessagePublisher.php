<?php

namespace SelrahcD\PostgresRabbitMq;

interface AmqpMessagePublisher
{
    public function publish(string $message, string $messageId): void;
}