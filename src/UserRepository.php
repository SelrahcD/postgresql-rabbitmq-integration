<?php

namespace SelrahcD\PostgresRabbitMq;

interface UserRepository
{
    public function registerUser(string $username): void;
}