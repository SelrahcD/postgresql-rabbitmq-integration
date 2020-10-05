<?php

use SelrahcD\PostgresRabbitMq\UserRepository\IntermittentFailureUserRepository;

class FailingUserRepositoryTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'USER_REPOSITORY' => IntermittentFailureUserRepository::class,
        ];
    }
}