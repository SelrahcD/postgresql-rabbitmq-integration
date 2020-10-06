<?php


use SelrahcD\PostgresRabbitMq\AmqpMessagePublisher\FailingAmqpMessagePublisher;
use SelrahcD\PostgresRabbitMq\LogMessage;

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
    public function check_logs(): void
    {
        self::assertEquals(
            (string)(new LogMessage())
                ->received($this->messageId)
                ->error('Couldn\'t publish to rabbitMQ')
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
Couldn't publish to rabbitMQ
EOL;

        self::assertEquals($expectedLogs, $this->process->getOutput());
    }
}