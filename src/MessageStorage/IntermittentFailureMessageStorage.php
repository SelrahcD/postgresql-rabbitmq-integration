<?php


namespace SelrahcD\PostgresRabbitMq\MessageStorage;


use SelrahcD\PostgresRabbitMq\MessageStorage;

class IntermittentFailureMessageStorage implements MessageStorage
{
    /**
     * @var GoodMessageStorage
     */
    private GoodMessageStorage $messageStorage;

    private int $failureToWrite;

    public function __construct(GoodMessageStorage $messageStorage, int $failureToWrite)
    {
        $this->messageStorage = $messageStorage;
        $this->failureToWrite = $failureToWrite;
    }

    public function recordMessageAsHandled(string $messageId): void
    {
        if($this->failureToWrite !== 0) {
            $this->failureToWrite--;
            throw new \Exception('Couldn\'t store message has handled');
        }

        $this->messageStorage->recordMessageAsHandled($messageId);
    }

    public function isAlreadyHandled(string $messageId)
    {
        $this->messageStorage->isAlreadyHandled($messageId);
    }
}