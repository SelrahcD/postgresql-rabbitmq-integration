<?php
use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToDeleteUnsentMessageFromDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            OUTBOX_DB_WRITER_DELETE_FAILURE => 1
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error(OUTBOX_DB_WRITER_DELETE_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}