<?php

namespace SelrahcD\PostgresRabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

class MessageHandler
{
    private MessageBus $messageBus;

    private UserRepository $userRepository;

    public function __construct(MessageBus $messageBus, UserRepository $userRepository)
    {
        $this->messageBus = $messageBus;
        $this->userRepository = $userRepository;
    }

    public function handle(AMQPMessage $message): void
    {
        $data = json_decode($message->body, true);
        $username = $data['username'];

        $this->userRepository->registerUser($username);

        $this->messageBus->publish([
            'eventName' => 'UserRegistered',
            'username' => $username
        ]);
    }

}