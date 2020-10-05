<?php

namespace SelrahcD\PostgresRabbitMq\UserRepository;

use PDO;
use SelrahcD\PostgresRabbitMq\UserRepository;

final class GoodUserRepository implements UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function registerUser(string $username): void
    {
        $sth = $this->pdo->prepare('INSERT INTO users (username) VALUES (:username)');
        $sth->bindParam('username', $username);
        $sth->execute();
    }

    public function isUsernameRegistered(string $username)
    {
        $sth = $this->pdo->prepare("SELECT count(id) FROM users WHERE username = :username");
        $sth->bindParam(':username', $username);
        $sth->execute();

        $count = $sth->fetchColumn();

        return $count > 0;
    }

    public function countOfUserRegisteredWith(string $username): int
    {
        $sth = $this->pdo->prepare("SELECT count(id) FROM users WHERE username = :username");
        $sth->bindParam(':username', $username);
        $sth->execute();

        return $sth->fetchColumn();
    }
}
