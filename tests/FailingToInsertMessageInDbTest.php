<?php


use SelrahcD\PostgresRabbitMq\LogMessage;
use SelrahcD\PostgresRabbitMq\OutboxDbWriter\FailingOutboxDbWriter;

class FailingToInsertMessageInDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'OUTBOX_DB_WRITER' => FailingOutboxDbWriter::class,
            'OUTBOX_DB_WRITER_INSERT_FAILURE' => 1
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t insert outbox message in DB')
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}