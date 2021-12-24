<?php

declare(strict_types=1);

namespace ahmetbarut\PhpRouter\Router;

use ErrorException;
use ahmetbarut\PhpRouter\{Exception\NotRouteFound, Middleware\Middleware, Reflection\Method};
use ahmetbarut\PhpRouter\Reflection\CallController;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;

use Closure;
use ReflectionFunction;
use Symfony\Component\HttpFoundation\Response;

class Router
{

    protected $route;

    /**
     * Stores all routes used.
     *
     * @var array
     */
    protected $router = [];

    /**
     *
     * @var Request
     */
    protected $request;
    /**
     * Stores named routes.
     *
     * @var static array
     */
    public static $nameList = [];

    /**
     * Stores temporary url.
     *
     * @var string
     */
    protected $path;

    /**
     * Index of error messages pages to display to the user.
     *
     * @var string
     */
    public static $error = null;

    /**
     * Controller directory.
     *
     * @var string
     */
    protected $namespace = "test\\";

    /**
     * Hata ayıklama modunu açar/kapatır.
     *
     * @var bool
     */
    public static $debugMode = true;

    protected $group = "";

    public $middleware;
    public $middlewareParams;

    protected $errors = [
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
    ];

    public function __construct($options = [])
    {
        $this->route = new Route;
        $this->request = Request::createFromGlobals();
        
        if (!empty($options)) {
            if (array_key_exists('namespace', $options)) {
                $this->namespace = $options['namespace'];
            }
            if (array_key_exists('error', $options)) {
                static::$error = $options['error'];
            }
            if (array_key_exists('debug', $options)) {
                static::$debugMode = $options['debug'];
            }
        }
    }

    /**
     * HTTP get yönteminde kullanılır
     *
     * @param string $uri
     * @param array|Closure $callback
     * @return static
     */
    public function get(string $uri, string|Closure $callback): static
    {
        $this->addHandler("GET", $uri, $callback);
        $this->path = $uri;

        return $this;
    }

    /**
     * HTTP POST yönteminde kullanılır
     *
     * @param string $uri
     * @param string|Closure $callback
     * @return Router
     */
    public function post(string $uri, string|Closure $callback): static
    {
        $this->addHandler("POST", $uri, $callback);
        $this->path = $uri;
        return $this;
    }

    /**
     * HTTP DELETE yönteminde kullanılır
     *
     * @param string $uri
     * @param array|Closure $callback
     * @return void
     */
    public function delete($uri, array|Closure $callback)
    {
        $this->addHandler("DELETE", $uri, $callback);
    }

    /**
     * HTTP PUT yönteminde kullanılır
     *
     * @param string $uri
     * @param array|Closure $callback
     * @return void
     */
    public function put(string $uri, array|Closure $callback)
    {
        $this->addHandler("PUT", $uri, $callback);
    }

    /**
     * HTTP PATCH yönteminde kullanılır
     *
     * @param string $uri
     * @param array|Closure $callback
     * @return void
     */
    public function pacth($uri, array|Closure $callback)
    {
        $this->addHandler("PATCH", $uri, $callback);
    }

    public function group($uri, $callback)
    {
        $this->group = $uri;
        call_user_func($callback);
        $this->group = "/";
    }

    /**
     * Verilen rotaları ekler
     *
     * @param string $method
     * @param string $path
     * @param string|Closure $callback
     * @return void
     */
    private function addHandler(string $method, string $path, string|Closure $callback): void
    {

        $this
            ->router[$method]
                    [rtrim($path, "/") == "" ? "/" : rtrim($path, "/")] =
                    clone $this->route->addRoute($path, $callback, $this->namespace, $this->group);
    }

    /**
     * Set middleware for route.
     * @param string $name
     * @return $this
     */
    public function middleware(string $name, ...$parameters): static
    {
        $this->middleware = $name;
        $this->middlewareParams = $parameters;
        return $this;
    }

    public function name($name): void
    {
        ($this->route->name($name, $this->middleware, $this->middlewareParams));
        static::$nameList[$name] = clone $this->route;
    }

    /**
     * Rotaları çalıştırır
     * @return Response
     * @throws ReflectionException|ErrorException
     */
    public function run(): Response
    {
        $response = new Response();
        
        if (!in_array($this->request->getMethod(), array_keys($this->router))) {
            $response->headers->set("Content-Type", "text/html");
            $response->setContent($this->errors[405]);
            $response->setStatusCode(405);

            return $response->send();
        }

        // Gelen HTTP isteğine göre ilgili rotaları çağırır.
        foreach ($this->router[$this->request->getMethod()] as $callback) {
            $parameters = [];

            if (str_contains($this->request->getRequestUri(), '?')) {
                $callback->query = strstr($this->request->getRequestUri(), '?');
            }
            // Rotayı hazırlanan düzenli ifadeyle eşleştirmeye çalışır
            if (preg_match("@" . $callback->regexpURL . "$@", $this->request->getRequestUri(), $parameters)) {

                // ilk parametre url'in tamamı olduğu için ilk değeri silmek zorunda.
                array_shift($parameters);
                // Rotada tanımlı parametreleri tanımlı değişkenler için hazırlar ve döndürür.
                // Örneğin: rotada /home/:user diye tanımlandı bunu "user" diye alır ve kaydeder.
                $routeParameters = rm_first_letter($callback->parameters);

                // Yöntemin ve rotanın parametrelerini birleştirir.
                $methodParameters = array_combine($routeParameters, $parameters);
                // $callback eğer diziyse yani bu controller ve method oluyor
                // ona göre aksiyon alıyor.
                if (is_string($callback->action)) {
                    $response
                        ->setContent(
                            (new CallController(
                                $this->namespace, 
                                $callback->action, $methodParameters
                                )
                                )->dispatch()
                                )->send();
                } else {
                    $response->setContent((new ReflectionFunction($callback->action))->invokeArgs($methodParameters))->send();
                }
            }
        }

        $response->setContent(sprintf("%s not found", $this->request->getRequestUri()));
        $response->headers->set('Content-Type', 'text/html');
        $response->setStatusCode(404);
        
        return $response->send();
        
        //throw new NotRouteFound(sprintf("%s not found", $this->request->getRequestUri()), 404);
    }

    public static function routes($name)
    {
        if (array_key_exists($name, (array) static::$nameList)) {
            return static::$nameList[$name];
        }
        return false;
    }
}
