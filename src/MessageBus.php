<?php


namespace SelrahcD\PostgresRabbitMq;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class MessageBus
{
    /**
     * @var AMQPChannel
     */
    private AMQPChannel $channel;

    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    public function publish(array $message)
    {
        $event = new AMQPMessage(json_encode($message));

        $this->channel->basic_publish($event, 'messages_out');
    }
}