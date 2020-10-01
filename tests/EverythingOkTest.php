<?php


use PhpAmqpLib\Message\AMQPMessage;

class EverythingOkTest extends PostgresqlRabbitmqIntegrationTest
{

    /**
     * @test
     */
    public function worker_receives_a_message(): void
    {
        $start = time();
        $messageReceived = false;
        while(!$messageReceived) {

            if(time() - $start > 5) {
                $this->fail('Message wasn\'t received after 5 seconds.');
            }

            $messageReceived = static::$logger->hasReceivedMessageReceivedLogForMessageId(static::$messageId->toString());
        }

        self::assertTrue($messageReceived);
    }

    /**
     * @test
     */
    public function message_is_marked_as_received_in_postgresql(): void
    {
        $start = time();
        $messageReceived = false;
        while(!$messageReceived) {

            if(time() - $start > 5) {
                $this->fail('Message wasn\'t stored in postgresql after 5 seconds.');
            }

            $messageReceived = static::$messageStorage->wasMessageOfIdReceived(static::$messageId->toString());
        }

        self::assertTrue($messageReceived);
    }

    /**
     * @test
     */
    public function user_is_stored_in_users_table(): void
    {
        $start = time();
        $messageReceived = false;
        while(!$messageReceived) {

            if(time() - $start > 5) {
                $this->fail('User wasn\'t stored in users table after 5 seconds');
            }

            $messageReceived = static::$userRepository->isUsernameRegistered(static::$username);
        }

        self::assertTrue($messageReceived);
    }

    /**
     * @test
     */
    public function userRegistered_event_is_published(): void
    {
        $receivedMessages = [];

        $callback = function (AMQPMessage $message) use(&$receivedMessages){
            $receivedMessages[] = $message->body;
        };

        static::$channel->basic_consume('outgoing_message_queue', '', false, true, false, false, $callback);

        $start = time();
        $messageReceived = false;
        $expectedMessage = json_encode(['eventName' => 'UserRegistered', 'username' => self::$username]);
        while(!$messageReceived) {

            static::$channel->wait(null, true);

            if(time() - $start > 5) {
                $this->fail('UserRegistered event wasn\'t received after 5 seconds');
            }

            foreach ($receivedMessages as $receivedMessage) {
                if($receivedMessage === $expectedMessage) {
                    $messageReceived = true;
                }
            }
        }

        self::assertTrue($messageReceived);
    }
}