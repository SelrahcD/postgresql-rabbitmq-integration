<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToInsertMessageInDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            OUTBOX_DB_WRITER_INSERT_FAILURE => 1
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error(OUTBOX_DB_WRITER_INSERT_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}