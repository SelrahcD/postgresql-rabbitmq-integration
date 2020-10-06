<?php

namespace SelrahcD\PostgresRabbitMq;

class LogMessage
{
    private string $message = '';

    public function acked(string $messageId): LogMessage
    {
        $this->message .= 'acked:' . $messageId . PHP_EOL;

        return $this;
    }

    public function nacked(string $messageId): LogMessage
    {
        $this->message .= 'nacked:' . $messageId . PHP_EOL;

        return $this;
    }

    public function error($errorMessage): LogMessage
    {
        $this->message .= 'error:' . $errorMessage . PHP_EOL;

        return $this;
    }

    public function received($messageId): LogMessage
    {
        $this->message .= 'received:' . $messageId . PHP_EOL;

        return $this;
    }

    public function handled($messageId): LogMessage
    {
        $this->message .= 'handled:' . $messageId . PHP_EOL;

        return $this;
    }

    public function __toString()
    {
        return $this->message;
    }
}