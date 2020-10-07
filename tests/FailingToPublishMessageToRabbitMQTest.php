<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToPublishMessageToRabbitMQTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            AMQP_MESSAGE_PUBLISH_FAILURES => 1,
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error(AMQP_MESSAGE_PUBLISH_FAILURES)
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}