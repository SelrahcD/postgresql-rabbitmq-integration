<?php


use SelrahcD\PostgresRabbitMq\AmqpMessageBus\IntermittentRabbitMQMessageBus;

class FailingToPublishMessageToRabbitMQTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'RABBITMQ_MESSAGE_BUS' => IntermittentRabbitMQMessageBus::class,
        ];
    }

    /**
     * @test
     */
    public function logs_error(): void
    {
        $expectedLogs = <<<EOL
Couldn't publish to rabbitMQ
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}