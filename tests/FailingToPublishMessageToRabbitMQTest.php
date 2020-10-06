<?php


use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher\IntermittentAmqpMessagePublisher;

class FailingToPublishMessageToRabbitMQTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'AMQP_MESSAGE_PUBLISHER' => IntermittentAmqpMessagePublisher::class,
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