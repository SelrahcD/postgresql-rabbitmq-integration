<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToReadUnsentMessageFromDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'OUTBOX_DB_WRITER_READ_FAILURE' => 1
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t read outbox unsent message from DB')
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}