<?php


namespace SelrahcD\PostgresRabbitMq\MessageStorage;


use SelrahcD\PostgresRabbitMq\MessageStorage\MessageStorage;

class FailingMessageStorage implements MessageStorage
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
            throw new \Exception(MESSAGE_STORAGE_WRITE_FAILURE);
        }

        $this->messageStorage->recordMessageAsHandled($messageId);
    }

    public function isAlreadyHandled(string $messageId): bool
    {
        if($this->readFailure !== 0) {
            $this->readFailure--;
            throw new \Exception(MESSAGE_STORAGE_READ_FAILURE);
        }

        return $this->messageStorage->isAlreadyHandled($messageId);
    }
}