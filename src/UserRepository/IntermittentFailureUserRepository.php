<?php


namespace SelrahcD\PostgresRabbitMq\UserRepository;


use SelrahcD\PostgresRabbitMq\UserRepository;

class IntermittentFailureUserRepository implements UserRepository
{
    private int $failureCount = 0;

    /**
     * @var GoodUserRepository
     */
    private GoodUserRepository $userRepository;


    public function __construct(GoodUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function registerUser(string $username): void
    {
        if($this->failureCount === 0) {
            $this->failureCount++;
            throw new \Exception('Temporary failure');
        }

        $this->userRepository->registerUser($username);
    }
}