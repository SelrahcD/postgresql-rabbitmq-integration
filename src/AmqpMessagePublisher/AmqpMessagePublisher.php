<?php

namespace SelrahcD\PostgresRabbitMq\AmqpMessagePublisher;

interface AmqpMessagePublisher
{
    public function publish(string $message, string $messageId): void;
}