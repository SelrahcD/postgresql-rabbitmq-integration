<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToStoreMessageHasHandledTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            MESSAGE_STORAGE_WRITE_FAILURE => 1,
        ];
    }


    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error(MESSAGE_STORAGE_WRITE_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}