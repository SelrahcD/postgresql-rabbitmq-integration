<?php

namespace SelrahcD\PostgresRabbitMq;

class Logger
{
    private string $messageLogFile;

    public function __construct(string $messageLogFile)
    {
        $this->messageLogFile = $messageLogFile;
    }

    public function logMessageReceived(string $messageId)
    {
        $log = (string) (new LogMessage())->received($messageId);

        file_put_contents($this->messageLogFile, $log, FILE_APPEND);
    }

    public function logMessageHandled(string $messageId)
    {
        $log = (string) (new LogMessage())->handled($messageId);

        file_put_contents($this->messageLogFile, $log, FILE_APPEND);
    }

    public function logMessageAcked(string $messageId): void
    {
        $log = (string) (new LogMessage())->acked($messageId);

        file_put_contents($this->messageLogFile, $log, FILE_APPEND);
    }

    public function logMessageNacked(string $messageId): void
    {
        $log = (string) (new LogMessage())->nacked($messageId);

        file_put_contents($this->messageLogFile, $log, FILE_APPEND);
    }

    public function logError(string $errorMessage): void
    {
        $log = (string) (new LogMessage())->error($errorMessage);
        file_put_contents($this->messageLogFile, $log, FILE_APPEND);
    }

    public function hasBeenAcked(string $messageId)
    {
        $searchedLog = (string) (new LogMessage())->acked($messageId);
        $logFile = fopen($this->messageLogFile, 'r');

        while (($line = fgets($logFile)) !== false) {
            if($line == $searchedLog) {
                return true;
            }
        }

        return false;
    }

    public function hasReceivedMessageReceivedLogForMessageIdAtLeast(string $messageId, int $minimumCalls)
    {
        $searchedLog = (string) (new LogMessage())->received($messageId);
        $logFile = fopen($this->messageLogFile, 'r');

        $count = 0;
        while (($line = fgets($logFile)) !== false) {
            if($line == $searchedLog) {
                $count++;
            }
        }

        return $count >= $minimumCalls;
    }

    public function hasHandledMessageAtLeast(string $messageId, int $minimumCalls)
    {
        $searchedLog = (string) (new LogMessage())->handled($messageId);
        $logFile = fopen($this->messageLogFile, 'r');

        $count = 0;
        while (($line = fgets($logFile)) !== false) {
            if($line == $searchedLog) {
                $count++;
            }
        }

        return $count >= $minimumCalls;
    }

    public function hasReceivedMessageReceivedLogForMessageId(string $messageId)
    {
        $searchedLog = (string) (new LogMessage())->received($messageId);
        $logFile = fopen($this->messageLogFile, 'r');

        while (($line = fgets($logFile)) !== false) {
            if($line == $searchedLog) {
                return true;
            }
        }

        return false;
    }

    public function allLogs(): string
    {
        return file_get_contents($this->messageLogFile, 'r');
    }

    public function hasBeenAckedAtLeast(string $messageId, int $minimumCalls)
    {
        $searchedLog = (string) (new LogMessage())->acked($messageId);
        $logFile = fopen($this->messageLogFile, 'r');

        $count = 0;
        while (($line = fgets($logFile)) !== false) {
            if($line == $searchedLog) {
                $count++;
            }
        }

        return $count >= $minimumCalls;
    }
}