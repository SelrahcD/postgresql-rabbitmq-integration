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

    public function logMessageReceived(AMQPMessage $message)
    {
        $headers = $message->get_properties();
        $messageId = $headers['message_id'];
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

    /**
     * @param string $messageId
     * @return string
     */
    private function formatMessageReceivedLog(string $messageId): string
    {
        return 'received:' . $messageId . PHP_EOL;
    }
}