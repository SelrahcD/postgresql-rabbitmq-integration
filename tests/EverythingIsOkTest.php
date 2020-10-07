<?php


use SelrahcD\PostgresRabbitMq\LogMessage;

class EverythingIsOkTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}