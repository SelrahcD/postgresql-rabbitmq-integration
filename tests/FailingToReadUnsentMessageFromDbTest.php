<?php


class FailingToReadUnsentMessageFromDbTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'OUTBOX_DB_WRITER' => IntermittentOutboxDbWriter::class,
            'OUTBOX_DB_WRITER_READ_FAILURE' => 1
        ];
    }

    /**
     * @test
     */
    public function logs_error(): void
    {
        $expectedLogs = <<<EOL
Couldn't read outbox unsent message from DB
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}