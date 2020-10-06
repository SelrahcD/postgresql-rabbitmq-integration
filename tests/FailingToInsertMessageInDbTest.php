<?php


use SelrahcD\PostgresRabbitMq\OutboxDbWriter\FailingOutboxDbWriter;

class FailingToInsertMessageInDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'OUTBOX_DB_WRITER' => FailingOutboxDbWriter::class,
            'OUTBOX_DB_WRITER_INSERT_FAILURE' => 1
        ];
    }

    /**
     * @test
     */
    public function logs_error(): void
    {
        $expectedLogs = <<<EOL
Couldn't insert outbox message in DB
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}