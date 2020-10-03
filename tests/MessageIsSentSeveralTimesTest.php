<?php

use PhpAmqpLib\Message\AMQPMessage;

class MessageIsSentSeveralTimesTest extends PostgresqlRabbitmqIntegrationTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::sendMessage();
    }

    /**
     * @test
     */
    public function user_is_stored_only_once_in_users_table(): void
    {
        $start = time();
        $messageReceivedAtLeastTwice = false;
        while(!$messageReceivedAtLeastTwice) {

            if(time() - $start > 5) {
                $this->fail('Message wasn\'t handled at least twice after 5 seconds');
            }

            $messageReceivedAtLeastTwice = static::$logger->hasHandledMessageAtLeast(static::$messageId->toString(), 2);
        }

        self::assertEquals(1, static::$userRepository->countOfUserRegisteredWith(static::$username));
    }

    /**
     * @test
     */
    public function userRegistered_event_is_published_only_once(): void
    {
        $receivedMessages = [];

        $start = time();
        $messageReceivedAtLeastTwice = false;
        while(!$messageReceivedAtLeastTwice) {

            if(time() - $start > 5) {
                $this->fail('Message wasn\'t handled at least twice after 5 seconds');
            }

            $messageReceivedAtLeastTwice = static::$logger->hasHandledMessageAtLeast(static::$messageId->toString(), 2);
        }

        $callback = function (AMQPMessage $message) use(&$receivedMessages){
            $receivedMessages[] = $message->body;
        };

        static::$channel->basic_consume('outgoing_message_queue', '', false, true, false, false, $callback);

        $start = time();
        $keepWaiting = true;
        while(static::$channel->is_consuming() && $keepWaiting && count($receivedMessages) < 2) {
            static::$channel->wait(null, true);
            $keepWaiting = time() - $start < 2;
        }

        $expectedMessage = json_encode(['eventName' => 'UserRegistered', 'username' => self::$username]);

        $matchingMessages = array_filter($receivedMessages, function($receivedMessage) use($expectedMessage) {
            return $receivedMessage === $expectedMessage;
        });

        self::assertCount(1, $matchingMessages);
    }


}