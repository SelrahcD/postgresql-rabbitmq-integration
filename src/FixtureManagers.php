<?php

namespace SelrahcD\PostgresRabbitMq;

use PDO;

class FixtureManagers
{
    public static function setupFixtures(PDO $pdo)
    {
       $pdo->exec("DROP TABLE IF EXISTS received_messages");
       $pdo->exec(
            "CREATE TABLE IF NOT EXISTS received_messages (
                         message_id VARCHAR(255) NOT NULL
         )");

       $pdo->exec("DROP TABLE IF EXISTS users");
       $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                         id SERIAL PRIMARY KEY,
                         username VARCHAR(255)
         )");

        $pdo->exec("DROP TABLE IF EXISTS messages_outbox");
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS messages_outbox (
                         message_id VARCHAR(255) NOT NULL,
                         body TEXT,
                         sent SMALLINT DEFAULT (0) NOT NULL
                         
         )");
    }
}