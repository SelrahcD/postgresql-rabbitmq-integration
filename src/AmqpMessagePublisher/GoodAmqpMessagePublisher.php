<?php

namespace SelrahcD\PostgresRabbitMq\AmqpMessagePublisher;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class GoodAmqpMessagePublisher implements AmqpMessagePublisher
{
    private AMQPChannel $channel;

    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    public function publish(string $message, string $messageId) : void
    {
        $event = new AMQPMessage($message, ['message_id' => $messageId]);

        $this->channel->basic_publish($event, 'messages_out');
    }
}