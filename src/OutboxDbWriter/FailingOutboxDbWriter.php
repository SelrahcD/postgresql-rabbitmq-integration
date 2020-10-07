<?php


namespace SelrahcD\PostgresRabbitMq\OutboxDbWriter;

use Exception;

class FailingOutboxDbWriter implements OutboxDbWriter
{
    /**
     * @var GoodOutboxBusDbWriter
     */
    private GoodOutboxBusDbWriter $outboxBusDbWriter;

    private int $outboxDbWriterInsertFailureCount;
    private int $outboxDbWriterReadFailureCount;
    private int $outboxDbWriterDeleteFailureCount;

    public function __construct(
        GoodOutboxBusDbWriter $outboxBusDbWriter,
        int $outboxDbWriterInsertFailureCount,
        int $outboxDbWriterReadFailureCount,
        int $outboxDbWriterDeleteFailureCount)
    {
        $this->outboxBusDbWriter = $outboxBusDbWriter;
        $this->outboxDbWriterInsertFailureCount = $outboxDbWriterInsertFailureCount;
        $this->outboxDbWriterReadFailureCount = $outboxDbWriterReadFailureCount;
        $this->outboxDbWriterDeleteFailureCount = $outboxDbWriterDeleteFailureCount;
    }

    public function insert(string $messageId, string $body): void
    {
        if ($this->outboxDbWriterInsertFailureCount !== 0) {
            $this->outboxDbWriterInsertFailureCount--;
            throw new Exception(OUTBOX_DB_WRITER_INSERT_FAILURE);
        }

        $this->outboxBusDbWriter->insert($messageId, $body);
    }

    public function unsentMessages(): array
    {
        if ($this->outboxDbWriterReadFailureCount !== 0) {
            $this->outboxDbWriterReadFailureCount--;
            throw new Exception(OUTBOX_DB_WRITER_READ_FAILURE);
        }

        return $this->outboxBusDbWriter->unsentMessages();
    }

    public function delete(string $messageId): void
    {
        if ($this->outboxDbWriterDeleteFailureCount !== 0) {
            $this->outboxDbWriterDeleteFailureCount--;
            throw new Exception(OUTBOX_DB_WRITER_DELETE_FAILURE);
        }
        $this->outboxBusDbWriter->delete($messageId);
    }
}