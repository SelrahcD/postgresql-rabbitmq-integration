<?php

namespace SelrahcD\PostgresRabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

class Logger
{
    private string $messageLogFile;

    public function __construct(string $messageLogFile)
    {
        $this->messageLogFile = $messageLogFile;
    }

    public function logMessageReceived(string $messageId)
    {
        file_put_contents($this->messageLogFile, $this->formatMessageReceivedLog($messageId), FILE_APPEND);
    }

    public function hasReceivedMessageReceivedLogForMessageId(string $messageId)
    {
        $logFile = fopen($this->messageLogFile, 'r');

        while (($line = fgets($logFile)) !== false) {
            if($line == $this->formatMessageReceivedLog($messageId)) {
                return true;
            }
        }

        return false;
    }

    public function logMessageHandled(string $messageId)
    {
        file_put_contents($this->messageLogFile, $this->formatMessageHandledLog($messageId), FILE_APPEND);
    }

    /**
     * @param string $messageId
     * @return string
     */
    private function formatMessageReceivedLog(string $messageId): string
    {
        return 'received:' . $messageId . PHP_EOL;
    }

    public function hasReceivedMessageReceivedLogForMessageIdAtLeast(string $messageId, int $minimumCalls)
    {
        $logFile = fopen($this->messageLogFile, 'r');

        $count = 0;
        while (($line = fgets($logFile)) !== false) {
            if($line == $this->formatMessageReceivedLog($messageId)) {
               $count++;
            }
        }

        return $count >= $minimumCalls;
    }

    public function hasHandledMessageAtLeast(string $messageId, int $minimumCalls)
    {
        $logFile = fopen($this->messageLogFile, 'r');

        $count = 0;
        while (($line = fgets($logFile)) !== false) {
            if($line == $this->formatMessageHandledLog($messageId)) {
                $count++;
            }
        }

        return $count >= $minimumCalls;
    }

    private function formatMessageHandledLog(string $messageId)
    {
        return 'handled:' . $messageId . PHP_EOL;
    }

    public function logMessageAcked(string $messageId)
    {
        file_put_contents($this->messageLogFile, $this->formatMessageAckedLog($messageId), FILE_APPEND);
    }

    public function logMessageNacked(string $messageId)
    {
        file_put_contents($this->messageLogFile, $this->formatMessageNackedLog($messageId), FILE_APPEND);
    }

    public function hasBeenAcked(string $messageId)
    {
        $logFile = fopen($this->messageLogFile, 'r');

        while (($line = fgets($logFile)) !== false) {
            if($line == $this->formatMessageAckedLog($messageId)) {
                return true;
            }
        }

        return false;
    }

    private function formatMessageAckedLog(string $messageId)
    {
        return 'acked:' . $messageId . PHP_EOL;
    }

    private function formatMessageNackedLog(string $messageId)
    {
        return 'nacked:' . $messageId . PHP_EOL;
    }
}