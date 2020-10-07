<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingUserRepositoryTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'USER_REPOSITORY_REGISTRATION_FAILURE' => 1
        ];
    }


    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t register user in DB')
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}