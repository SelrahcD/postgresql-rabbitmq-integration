<?php


namespace SelrahcD\PostgresRabbitMq\MessageStorage;


use SelrahcD\PostgresRabbitMq\MessageStorage;

class IntermittentFailureMessageStorage implements MessageStorage
{
    /**
     * @var GoodMessageStorage
     */
    private GoodMessageStorage $messageStorage;

    private int $writeFailure;
    private int $readFailure;

    public function __construct(GoodMessageStorage $messageStorage, int $writeFailure, int $readFailure)
    {
        $this->messageStorage = $messageStorage;
        $this->writeFailure = $writeFailure;
        $this->readFailure = $readFailure;
    }

    public function recordMessageAsHandled(string $messageId): void
    {
        if($this->writeFailure !== 0) {
            $this->writeFailure--;
            throw new \Exception('Couldn\'t store message has handled');
        }

        $this->messageStorage->recordMessageAsHandled($messageId);
    }

    public function isAlreadyHandled(string $messageId): bool
    {
        if($this->readFailure !== 0) {
            $this->readFailure--;
            throw new \Exception('Couldn\'t read if message was handled');
        }

        return $this->messageStorage->isAlreadyHandled($messageId);
    }
}