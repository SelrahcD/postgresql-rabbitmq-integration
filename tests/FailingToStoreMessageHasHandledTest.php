<?php


use SelrahcD\PostgresRabbitMq\MessageStorage\IntermittentFailureMessageStorage;

class FailingToStoreMessageHasHandledTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'MESSAGE_STORAGE' => IntermittentFailureMessageStorage::class
        ];
    }


}