<?php


use SelrahcD\PostgresRabbitMq\MessageStorage\FailingMessageStorage;
use SelrahcD\PostgresRabbitMq\OutboxDbWriter\FailingOutboxDbWriter;

class FailingToDeleteUnsentMessageFromDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'OUTBOX_DB_WRITER' => FailingOutboxDbWriter::class,
            'MESSAGE_STORAGE' => FailingMessageStorage::class,
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