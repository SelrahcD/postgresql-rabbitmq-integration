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

    public function setupQueues(string $outQueueName = 'outgoing_message_queue')
    {
        $this->channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT);
        $this->channel->queue_declare('incoming_message_queue');
        $this->channel->queue_bind('incoming_message_queue', 'messages_in');

        $this->channel->exchange_declare('messages_out', AMQPExchangeType::DIRECT);
        $this->channel->queue_declare($outQueueName);
        $this->channel->queue_bind($outQueueName, 'messages_out');
    }
}