<?php


use SelrahcD\PostgresRabbitMq\MessageStorage\FailingMessageStorage;

class FailingToKnowIfMessageWasHandledTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'MESSAGE_STORAGE' => FailingMessageStorage::class,
            'MESSAGE_STORAGE_READ_FAILURE' => 1,
        ];
    }
    
    /**
     * @test
     */
     public function logs_error(): void
     {
         $expectedLogs = <<<EOL
Couldn't read if message was handled
EOL;

         self::assertEquals($expectedLogs, $this->process->getOutput());
     }
}