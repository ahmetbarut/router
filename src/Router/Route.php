<?php

namespace ahmetbarut\PhpRouter\Router;

use ahmetbarut\PhpRouter\Middleware\Middleware;
use Closure;

class Route
{
    /**
     * Store uniq route name.
     *
     * @var string
     */
    public string $name;

    /**
     * Store route action
     *
     * @var string|Closure
     */
    public string|Closure $action;

    /**
     * Store route uri.
     *
     * @var string
     */
    public string $uri;

    /**
     * Store regular expressions.
     *
     * @var string
     */
    public string $regexpURL;

    /**
     * Store route parameters.
     *
     * @var array
     */
    public array $parameters;

    /**
     * Store route namespace.
     *
     * @var string
     */
    public string $namespace;

    /**
     * Store route group name.
     *
     * @var string
     */
    public string $group;

    public Middleware $middleware;

    /**
     * Store route parameters.
     *
     * @param string $path
     * @param string|Closure $callback
     * @param string $namespace
     * @param string $group
     * @return static
     */
    public function addRoute(string $path, string|Closure $callback, string $namespace = "", string $group = ""): static
    {
        // Ready to edit and prepare according to the given regular expressions
        $path = rtrim($path, "/") === "" ? "/" : rtrim($path, "/");

        $this->uri($path);

        $this->regexpURL(preg_replace("/([:][a-z0-9_]+|[?]$)/", "([\w-]+)", $path));

        $this->parameters(preg_filter("/(^[:][a-z0-9_]+|[?]$)/", "$0", explode("/", $path)));

        $this->action($callback);

        $this->namespace($namespace);

        $this->group($group);

        return $this;
    }

    /**
     * Set route name.
     *
     * @param string $name
     * @return static
     */
    public function name(string $name, $middleware, $middlewareParams)
    {
        $this->name = $name;
        $this->middleware = new Middleware($middleware,$this ,$middlewareParams);
        return $this;
    }
    public function middleware(string $name)
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Set route action.
     *
     * @param string|Closure $action
     * @return static
     */
    protected function action(string|Closure $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Set route group.
     *
     * @param string $group
     * @return static
     */
    public function group(string $group): static
    {
        $this->regexpURL = rtrim($group, '/') . '/' . ltrim($this->regexpURL, '/');
        $this->group = $group;
        return $this;
    }

    /**
     * Set route parameters.
     *
     * @param array $parameters
     * @return static
     */
    protected function parameters(array $parameters): static
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Set regular expressions url.
     *
     * @param string $regexpURL
     * @return static
     */
    public function regexpURL(string $regexpURL): static
    {
        $this->regexpURL = $regexpURL;
        return $this;
    }

    /**
     * Set route uri.
     *
     * @param string $uri
     * @return static
     */
    protected function uri(string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Set route namespace.
     *
     * @param string $namespace
     * @return static
     */
    public function namespace(string $namespace): static
    {
        $this->namespace = $namespace;
        return $this;
    }
}
