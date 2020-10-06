<?php


namespace SelrahcD\PostgresRabbitMq\UserRepository;


use SelrahcD\PostgresRabbitMq\UserRepository\UserRepository;

class FailingUserRepository implements UserRepository
{
    private GoodUserRepository $userRepository;

    private int $registrationFailureCount;

    public function __construct(GoodUserRepository $userRepository, int $registrationFailureCount)
    {
        $this->userRepository = $userRepository;
        $this->registrationFailureCount = $registrationFailureCount;
    }

    public function registerUser(string $username): void
    {
        if($this->registrationFailureCount !== 0) {
            $this->registrationFailureCount--;
            throw new \Exception('Couldn\'t register user in DB');
        }

        $this->userRepository->registerUser($username);
    }
}