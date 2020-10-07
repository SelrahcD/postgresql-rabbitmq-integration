<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToKnowIfMessageWasHandledTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'MESSAGE_STORAGE_READ_FAILURE' => 1,
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t read if message was handled')
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}