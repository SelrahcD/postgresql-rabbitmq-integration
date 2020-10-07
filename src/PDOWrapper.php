<?php


namespace SelrahcD\PostgresRabbitMq;


class PDOWrapper extends \PDO
{
    private int $beginTransactionExpectedFailureCount;
    private int $commitTransactionExpectedFailureCount;

    public function __construct(string $dsn, int $beginTransactionExpectedFailureCount, int $commitTransactionExpectedFailureCount)
    {
        parent::__construct($dsn);
        $this->beginTransactionExpectedFailureCount = $beginTransactionExpectedFailureCount;
        $this->commitTransactionExpectedFailureCount = $commitTransactionExpectedFailureCount;
    }

    public function beginTransaction()
    {
        if($this->beginTransactionExpectedFailureCount !== 0) {
            $this->beginTransactionExpectedFailureCount--;
            throw new \Exception(PDO_START_TRANSACTION_FAILURE);
        }

        parent::beginTransaction();
    }

    public function commit()
    {
        if($this->commitTransactionExpectedFailureCount !== 0) {
            $this->commitTransactionExpectedFailureCount--;
            $this->rollBack();
            return false;
        }

        return parent::commit();
    }


}