<?php

namespace SelrahcD\PostgresRabbitMq\UserRepository;

interface UserRepository
{
    public function registerUser(string $username): void;
}