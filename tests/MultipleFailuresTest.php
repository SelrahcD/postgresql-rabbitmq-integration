<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class MultipleFailuresTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            MESSAGE_STORAGE_READ_FAILURE => 1,
            PDO_START_TRANSACTION_FAILURE => 1,
            USER_REPOSITORY_REGISTRATION_FAILURE => 1,
            OUTBOX_DB_WRITER_INSERT_FAILURE => 1,
            PDO_COMMIT_TRANSACTION_FAILURE => 2,
            AMQP_MESSAGE_PUBLISH_FAILURES => 1,
            OUTBOX_DB_WRITER_DELETE_FAILURE => 1
        ];
    }


    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error(PDO_START_TRANSACTION_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->error(MESSAGE_STORAGE_READ_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->error(USER_REPOSITORY_REGISTRATION_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->error(OUTBOX_DB_WRITER_INSERT_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->error(PDO_COMMIT_TRANSACTION_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->error(PDO_COMMIT_TRANSACTION_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->error(AMQP_MESSAGE_PUBLISH_FAILURES)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->error(OUTBOX_DB_WRITER_DELETE_FAILURE)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}