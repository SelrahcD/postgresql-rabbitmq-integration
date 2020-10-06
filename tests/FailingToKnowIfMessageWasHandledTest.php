<?php


use SelrahcD\PostgresRabbitMq\LogMessage;
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
    public function check_logs(): void
    {
        self::assertEquals(
            (string)(new LogMessage())
                ->received($this->messageId)
                ->error('Couldn\'t read if message was handled')
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
Couldn't read if message was handled
EOL;

         self::assertEquals($expectedLogs, $this->process->getOutput());
     }
}