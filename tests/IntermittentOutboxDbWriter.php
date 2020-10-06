<?php


use SelrahcD\PostgresRabbitMq\GoodOutboxBusDbWriter;
use SelrahcD\PostgresRabbitMq\OutboxMessageBusDbWriter;

class IntermittentOutboxDbWriter implements OutboxMessageBusDbWriter
{
    /**
     * @var GoodOutboxBusDbWriter
     */
    private GoodOutboxBusDbWriter $outboxBusDbWriter;

    private int $outboxDbWriterInsertFailureCount;
    private int $outboxDbWriterReadFailureCount;

    public function __construct(GoodOutboxBusDbWriter $outboxBusDbWriter, int $outboxDbWriterInsertFailureCount, int $outboxDbWriterReadFailureCount)
    {
        $this->outboxBusDbWriter = $outboxBusDbWriter;
        $this->outboxDbWriterInsertFailureCount = $outboxDbWriterInsertFailureCount;
        $this->outboxDbWriterReadFailureCount = $outboxDbWriterReadFailureCount;
    }

    public function insert(string $messageId, string $body): void
    {
        if($this->outboxDbWriterInsertFailureCount !== 0) {
            $this->outboxDbWriterInsertFailureCount--;
            throw new Exception('Couldn\'t insert outbox message in DB');
        }

        $this->outboxBusDbWriter->insert($messageId, $body);
    }

    public function unsentMessages(): array
    {
        if($this->outboxDbWriterReadFailureCount !== 0) {
            $this->outboxDbWriterReadFailureCount--;
            throw new Exception('Couldn\'t read outbox unsent message from DB');
        }

        return $this->outboxBusDbWriter->unsentMessages();
    }

    public function delete(string $messageId): void
    {
        $this->outboxBusDbWriter->delete($messageId);
    }
}