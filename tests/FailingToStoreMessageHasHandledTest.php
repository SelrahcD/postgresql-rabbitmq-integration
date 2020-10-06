<?php


use SelrahcD\PostgresRabbitMq\MessageStorage\FailingMessageStorage;

class FailingToStoreMessageHasHandledTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'MESSAGE_STORAGE' => FailingMessageStorage::class,
            'MESSAGE_STORAGE_WRITE_FAILURE' => 1,
        ];
    }
    
    /**
     * @test
     */
     public function logs_error(): void
     {
         $expectedLogs = <<<EOL
Couldn't store message has handled
EOL;

         self::assertEquals($expectedLogs, $this->process->getOutput());
     }
}