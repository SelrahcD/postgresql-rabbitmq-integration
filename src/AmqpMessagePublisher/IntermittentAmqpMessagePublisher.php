<?php

namespace SelrahcD\PostgresRabbitMq\AmqpMessagePublisher;

use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher;

class IntermittentAmqpMessagePublisher implements AmqpMessagePublisher
{
    private GoodAmqpMessagePublisher $messagePublisher;

    private int $expectedFailureCount = 1;

    /**
     * IntermittentRabbitMQMessageBus constructor.
     */
    public function __construct(GoodAmqpMessagePublisher $messagePublisher)
    {
        $this->messagePublisher = $messagePublisher;
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