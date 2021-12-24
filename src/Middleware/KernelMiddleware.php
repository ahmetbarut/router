<?php

namespace ahmetbarut\PhpRouter\Middleware;

abstract class KernelMiddleware
{
    protected array $middlewares = [
        'auth' => \App\Middleware\TestMiddleware::class,
    ];

    public function getMiddleware($name)
    {
        if (array_key_exists($name, $this->middlewares)) {
            return $this->middlewares[$name];
        }
        if (in_array($name, $this->middlewares)) {
            return $this->middlewares[$name];
        }
        return false;
    }
}