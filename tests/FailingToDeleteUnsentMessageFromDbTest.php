<?php


use SelrahcD\PostgresRabbitMq\MessageStorage\IntermittentFailureMessageStorage;

class FailingToDeleteUnsentMessageFromDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'OUTBOX_DB_WRITER' => IntermittentOutboxDbWriter::class,
            'MESSAGE_STORAGE' => IntermittentFailureMessageStorage::class,
            'OUTBOX_DB_WRITER_DELETE_FAILURE' => 1
        ];
    }

    /**
     * @test
     */
    public function logs_error(): void
    {
        $expectedLogs = <<<EOL
Couldn't delete outbox message from DB
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}