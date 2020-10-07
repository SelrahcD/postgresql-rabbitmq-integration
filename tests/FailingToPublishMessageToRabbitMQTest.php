<?php


use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher\FailingAmqpMessagePublisher;
use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToPublishMessageToRabbitMQTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'AMQP_MESSAGE_PUBLISHER' => FailingAmqpMessagePublisher::class,
            'AMQP_MESSAGE_PUBLISH_FAILURES' => 1,
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t publish to rabbitMQ')
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}