<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class MessageIsSentSeveralTimesTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function messagesToSend(): array
    {
        return [$this->buildCreateUserMessage(), $this->buildCreateUserMessage()];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }
}