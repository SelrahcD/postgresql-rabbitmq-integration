<?php


use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher\FailingAmqpMessagePublisher;

class FailingToPublishMessageToRabbitMQTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'AMQP_MESSAGE_PUBLISHER' => FailingAmqpMessagePublisher::class,
            'AMQP_MESSAGE_PUBLISH_FAILURES' => 1,
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