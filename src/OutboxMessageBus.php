<?php

namespace SelrahcD\PostgresRabbitMq;

use Ramsey\Uuid\Uuid;

class OutboxMessageBus implements MessageBus
{
    private OutboxMessageBusDbWriter $outboxMessageBusDbWriter;

    private AmqpMessagePublisher $messagePublisher;

    public function __construct(OutboxMessageBusDbWriter $outboxMessageBusDbWriter, AmqpMessagePublisher$messagePublisher)
    {
        $this->outboxMessageBusDbWriter = $outboxMessageBusDbWriter;
        $this->messagePublisher = $messagePublisher;
    }

    public function publish(array $message): void
    {
        $messageBody = json_encode($message);
        $id = Uuid::uuid4()->toString();
        $this->outboxMessageBusDbWriter->insert($id, $messageBody);
    }

    public function sendMessages()
    {
        $unsentMessages = $this->outboxMessageBusDbWriter->unsentMessages();

        foreach ($unsentMessages as $unsentMessage) {
            $this->messagePublisher->publish($unsentMessage['body'], $unsentMessage['message_id']);
            $this->outboxMessageBusDbWriter->delete($unsentMessage['message_id']);
        }
    }
}