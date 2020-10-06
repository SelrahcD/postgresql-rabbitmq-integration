<?php


use SelrahcD\PostgresRabbitMq\LogMessage;

class EverythingIsOkTest extends PostgresqlRabbitmqIntegrationTest
{
    /**
     * @test
     */
     public function check_logs(): void
     {
         self::assertEquals(
             (string)(new LogMessage())
                 ->received($this->messageId)
                 ->handled($this->messageId)
                 ->acked($this->messageId)
             ,
             $this->logger->allLogs()
         );
     }
}