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

    /**
     * @test
     */
    public function check_logs(): void
    {
        self::assertEquals(
            (string)(new LogMessage())
                ->received($this->messageId)
                ->error('Couldn\'t commit')
                ->nacked($this->messageId)
                ->received($this->messageId)
                ->handled($this->messageId)
                ->acked($this->messageId)
            ,
            $this->logger->allLogs()
        );
    }

    /**
     * @test
     */
    public function logs_error(): void
    {
        $expectedLogs = <<<EOL
Couldn't commit
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}