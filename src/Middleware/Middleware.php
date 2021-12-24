<?php

namespace ahmetbarut\PhpRouter\Middleware;

use ahmetbarut\PhpRouter\Router\Route;

class Middleware
{
    public function __construct(
        public string $name,
        public Route $route,
        public array $parameters = [])
    {}

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getName(): string
    {
        return $this->name;
    }
}