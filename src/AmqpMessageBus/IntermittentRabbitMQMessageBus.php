<?php


namespace SelrahcD\PostgresRabbitMq\AmqpMessageBus;


use SelrahcD\PostgresRabbitMq\MessageBus;

class IntermittentRabbitMQMessageBus implements MessageBus
{
    /**
     * @var GoodAmqpMessageBus
     */
    private GoodAmqpMessageBus $amqpMessageBus;

    private int $expectedFailureCount = 1;

    /**
     * IntermittentRabbitMQMessageBus constructor.
     */
    public function __construct(GoodAmqpMessageBus $amqpMessageBus)
    {
        $this->amqpMessageBus = $amqpMessageBus;
    }

    public function publish(array $message): void
    {
        if($this->expectedFailureCount !== 0) {
            $this->expectedFailureCount--;
            throw new \Exception('Couldn\'t publish to rabbitMQ');
        }

        $this->amqpMessageBus->publish($message);
    }
}