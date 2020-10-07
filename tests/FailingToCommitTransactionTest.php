<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToCommitTransactionTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'PDO_COMMIT_TRANSACTION_FAILURE' => 1,
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t commit')
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}