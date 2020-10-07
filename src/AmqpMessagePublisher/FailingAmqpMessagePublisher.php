<?php

namespace SelrahcD\PostgresRabbitMq\AmqpMessagePublisher;

class FailingAmqpMessagePublisher implements AmqpMessagePublisher
{
    private GoodAmqpMessagePublisher $messagePublisher;

    private int $expectedFailureCount = 1;

    public function __construct(GoodAmqpMessagePublisher $messagePublisher, int $expectedFailureCount)
    {
        $this->messagePublisher = $messagePublisher;
        $this->expectedFailureCount = $expectedFailureCount;
    }


    public function publish(string $message, string $messageId): void
    {
        if($this->expectedFailureCount !== 0) {
            $this->expectedFailureCount--;
            throw new \Exception(AMQP_MESSAGE_PUBLISH_FAILURES);
        }

        $this->messagePublisher->publish($message, $messageId);
    }
}