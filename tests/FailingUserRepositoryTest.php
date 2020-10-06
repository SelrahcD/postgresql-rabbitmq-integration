<?php

use SelrahcD\PostgresRabbitMq\LogMessage;
use SelrahcD\PostgresRabbitMq\UserRepository\FailingUserRepository;

class FailingUserRepositoryTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'USER_REPOSITORY_REGISTRATION_FAILURE' => 1
        ];
    }

    /**
     * @test
     */
    public function check_logs(): void
    {
        self::assertEquals(
            (string)(new LogMessage())
                ->received($this->messageId)
                ->error('Couldn\'t register user in DB')
                ->nacked($this->messageId)
                ->received($this->messageId)
                ->handled($this->messageId)
                ->acked($this->messageId)
            ,
            $this->logger->allLogs()
        );
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