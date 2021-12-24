<?php

namespace ahmetbarut\PhpRouter\Middleware;

abstract class BaseMiddleware
{
    abstract public function handle(?array $parameter = []): bool;
}