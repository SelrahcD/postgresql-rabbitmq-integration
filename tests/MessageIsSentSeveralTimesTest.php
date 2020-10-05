<?php


class MessageIsSentSeveralTimesTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function messagesToSend(): array
    {
        return [$this->buildCreateUserMessage(), $this->buildCreateUserMessage()];
    }
}