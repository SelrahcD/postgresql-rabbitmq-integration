<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;

return [

    PDO::class => function() {
        $postgresHost = getenv('POSTGRES_HOST');
        $postgresDB = getenv('POSTGRES_DB');
        $postgresUsername = getenv('POSTGRES_USER');
        $postgresPassword = getenv('POSTGRES_PASSWORD');

        $dsn = "pgsql:host=$postgresHost;port=5432;dbname=$postgresDB;user=$postgresUsername;password=$postgresPassword";

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    },
    AMQPStreamConnection::class => function() {
        return new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_DEFAULT_USER'),
            getenv('RABBITMQ_DEFAULT_PASS')
        );
    }
];