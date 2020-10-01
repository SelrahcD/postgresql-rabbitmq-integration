<?php


namespace SelrahcD\PostgresRabbitMq;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exchange\AMQPExchangeType;

class QueueExchangeManager
{
    public static function setupQueues(AMQPChannel $channel)
    {
        $channel->exchange_declare('messages_in', AMQPExchangeType::DIRECT);
        $channel->queue_declare('incoming_message_queue');
        $channel->queue_bind('incoming_message_queue', 'messages_in');

        $channel->exchange_declare('messages_out', AMQPExchangeType::DIRECT, false, false, false);
        $channel->queue_declare('outgoing_message_queue',false, false, false , false);
        $channel->queue_bind('outgoing_message_queue', 'messages_out');
    }
}