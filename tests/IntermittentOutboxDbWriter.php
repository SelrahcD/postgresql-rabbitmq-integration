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

    public function __construct(GoodOutboxBusDbWriter $outboxBusDbWriter, int $outboxDbWriterInsertFailureCount)
    {
        $this->outboxBusDbWriter = $outboxBusDbWriter;
        $this->outboxDbWriterInsertFailureCount = $outboxDbWriterInsertFailureCount;
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
        return $this->outboxBusDbWriter->unsentMessages();
    }

    public function delete(string $messageId): void
    {
        $this->outboxBusDbWriter->delete($messageId);
    }
}