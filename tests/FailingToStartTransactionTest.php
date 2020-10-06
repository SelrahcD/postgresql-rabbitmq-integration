<?php

class FailingToStartTransactionTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'PDO_START_TRANSACTION_FAILURE' => 1,
        ];
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