<?php


namespace SelrahcD\PostgresRabbitMq\AmqpMessageBus;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use SelrahcD\PostgresRabbitMq\MessageBus;

class GoodAmqpMessageBus implements MessageBus
{
    /**
     * @var AMQPChannel
     */
    private AMQPChannel $channel;

    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    public function publish(array $message) : void
    {
        $event = new AMQPMessage(json_encode($message));

        $this->channel->basic_publish($event, 'messages_out');
    }
}