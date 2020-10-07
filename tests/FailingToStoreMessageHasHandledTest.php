<?php


use SelrahcD\PostgresRabbitMq\LogMessage;
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


    protected function expectedLogs(): LogMessage
    {
        return (new LogMessage())
            ->received($this->messageId)
            ->error('Couldn\'t store message has handled')
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
Couldn't store message has handled
EOL;

         self::assertEquals($expectedLogs, $this->process->getOutput());
     }
}