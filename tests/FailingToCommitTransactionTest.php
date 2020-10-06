<?php

class FailingToCommitTransactionTest extends PostgresqlRabbitmqIntegrationTest
{
    protected function implementations()
    {
        return [
            'PDO_COMMIT_TRANSACTION_FAILURE' => 1,
        ];
    }
}