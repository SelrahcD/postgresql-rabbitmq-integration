<?php

namespace SelrahcD\PostgresRabbitMq\AmqpMessagePublisher;

use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher\AmqpMessagePublisher;

class FailingAmqpMessagePublisher implements AmqpMessagePublisher
{
    private GoodAmqpMessagePublisher $messagePublisher;

    private int $expectedFailureCount = 1;

    /**
     * IntermittentRabbitMQMessageBus constructor.
     */
    public function __construct(GoodAmqpMessagePublisher $messagePublisher, int $expectedFailureCount)
    {
        $this->messagePublisher = $messagePublisher;
        $this->expectedFailureCount = $expectedFailureCount;
    }


    public function publish(string $message, string $messageId): void
    {
        if($this->expectedFailureCount !== 0) {
            $this->expectedFailureCount--;
            throw new \Exception('Couldn\'t publish to rabbitMQ');
        }

        $this->messagePublisher->publish($message, $messageId);
    }
}