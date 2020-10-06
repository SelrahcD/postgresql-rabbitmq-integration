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

    /**
     * @test
     */
    public function logs_error(): void
    {
        $expectedLogs = <<<EOL
Couldn't register user in DB
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}