<?php


use SelrahcD\PostgresRabbitMq\LogMessage;

class MessageIsSentSeveralTimesTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function messagesToSend(): array
    {
        return [$this->buildCreateUserMessage(), $this->buildCreateUserMessage()];
    }

    /**
     * @test
     */
    public function check_logs(): void
    {
        self::assertEquals(
            (string)(new LogMessage())
                ->received($this->messageId)
                ->handled($this->messageId)
                ->acked($this->messageId)
                ->received($this->messageId)
                ->handled($this->messageId)
                ->acked($this->messageId)
            ,
            $this->logger->allLogs()
        );
    }
}