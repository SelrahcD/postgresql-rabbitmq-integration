<?php

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
    public function logs_error(): void
    {
        $expectedLogs = <<<EOL
Couldn't commit
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}