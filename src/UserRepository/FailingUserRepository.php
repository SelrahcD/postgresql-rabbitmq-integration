<?php

namespace SelrahcD\PostgresRabbitMq\UserRepository;

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
            throw new \Exception(USER_REPOSITORY_REGISTRATION_FAILURE);
        }

        $this->userRepository->registerUser($username);
    }
}