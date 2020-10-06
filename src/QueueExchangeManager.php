<?php

namespace SelrahcD\PostgresRabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exchange\AMQPExchangeType;

class QueueExchangeManager {

    private AMQPChannel $channel;

    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    public function setupQueues()
    {
        $this->channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT,false, false, false);
        $this->channel->queue_declare('incoming_message_queue', false, false, false, false);
        $this->channel->queue_bind('incoming_message_queue', 'messages_in');

        $this->channel->exchange_declare('messages_out', AMQPExchangeType::DIRECT, false, false, false);
        $this->channel->queue_declare('outgoing_message_queue', false, false, false, false);
        $this->channel->queue_bind('outgoing_message_queue', 'messages_out');
    }
}