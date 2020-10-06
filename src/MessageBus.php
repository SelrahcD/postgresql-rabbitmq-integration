<?php

namespace SelrahcD\PostgresRabbitMq;

interface MessageBus
{
    public function publish(array $message): void;
}