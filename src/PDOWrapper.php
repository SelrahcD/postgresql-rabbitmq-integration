<?php


namespace SelrahcD\PostgresRabbitMq;


class PDOWrapper extends \PDO
{
    private int $beginTransactionExpectedFailureCount;

    public function __construct(string $dsn, int $beginTransactionExpectedFailureCount)
    {
        parent::__construct($dsn);
        $this->beginTransactionExpectedFailureCount = $beginTransactionExpectedFailureCount;
    }

    public function beginTransaction()
    {
        if($this->beginTransactionExpectedFailureCount !== 0) {
            $this->beginTransactionExpectedFailureCount--;
            throw new \Exception('Couldn\'t start transaction');
        }

        parent::beginTransaction();
    }


}