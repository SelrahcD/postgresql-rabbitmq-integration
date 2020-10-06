<?php


namespace SelrahcD\PostgresRabbitMq\MessageStorage;


use SelrahcD\PostgresRabbitMq\MessageStorage;

class IntermittentFailureMessageStorage implements MessageStorage
{
    /**
     * @var GoodMessageStorage
     */
    private GoodMessageStorage $messageStorage;

    private int $failureCount = 0;

    public function __construct(GoodMessageStorage $messageStorage)
    {
        $this->messageStorage = $messageStorage;
    }

    public function recordMessageAsHandled(string $messageId): void
    {
        if($this->failureCount === 0) {
            $this->failureCount++;
            throw new \Exception('Temporary failure');
        }

        $this->messageStorage->recordMessageAsHandled($messageId);
    }

    public function isAlreadyHandled(string $messageId)
    {
        $this->messageStorage->recordMessageAsHandled($messageId);
    }
}