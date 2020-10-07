<?php

use SelrahcD\PostgresRabbitMq\LogMessage;

class FailingToStartTransactionTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'PDO_START_TRANSACTION_FAILURE' => 1,
        ];
    }

    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t start transaction')
            ->nacked($this->messageId)
            ->received($this->messageId)
            ->handled($this->messageId)
            ->acked($this->messageId);
    }

    /**
     * @test
     */
     public function logs_error(): void
     {
         $expectedLogs = <<<EOL
Couldn't start transaction
EOL;

         self::assertEquals($expectedLogs, $this->process->getOutput());
     }
}