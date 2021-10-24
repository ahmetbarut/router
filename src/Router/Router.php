<?php

declare(strict_types=1);

namespace ahmetbarut\PhpRouter\Router;

use ahmetbarut\PhpRouter\{
    Exception\NotRouteFound,
    Reflection\Method,
    Request\Request
};
use Closure;
use ReflectionFunction;

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

    public function __construct($options = [])
    {
        $this->route = new Route;

        $this->request = new Request;

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
    public function get($uri,  string|Closure $callback)
    {
        $this->addHandler("GET", $uri, $callback);
        $this->path = $uri;

        return $this;
    }

    public function name($name)
    {
        $this->route->name($name);
        static::$nameList[$name] = clone $this->route;
        return $this;
    }

    /**
     * HTTP POST yönteminde kullanılır
     *
     * @param string $uri
     * @param array|Closure $callback
     * @return void
     */
    public function post($uri, string|Closure $callback)
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
    public function put($uri, array|Closure $callback)
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
     * @param array|Closure $callback
     * @return static
     */
    private function addHandler($method, $path, $callback)
    {

        $this->router[$method][rtrim($path, "/") == "" ? "/" : rtrim($path, "/")]
            = clone $this->route->addRoute($path, $callback, $this->namespace, $this->group);
    }

    /**
     * Rotaları çalıştırır
     * @return \ahmetbarut\PhpRouter\Reflection\Method
     */
    public function run()
    {
        if (!in_array($this->request->method, array_keys($this->router))) {
            http_response_code(405);

            header("Method not allowed HTTP/1.1", response_code: 405);
            exit;
        }

        // Gelen HTTP isteğine göre ilgili rotaları çağırır.
        foreach ($this->router[Request::method()] as $callback) {
            $parameters = [];

            if (false !== strpos(Request::uri(), '?')) {
                $callback->query = strstr(Request::uri(), '?');
            }
            // Rotayı hazırlanan düzenli ifadeyle eşleştirmeye çalışır
            if (preg_match("@" . $callback->regexpURL . "$@", $this->request->url, $parameters)) {

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
                    return new Method($this->namespace, $callback->action, $methodParameters);
                } else {
                    return (new ReflectionFunction($callback->action))->invokeArgs($methodParameters);
                }
            }
        }
        throw new NotRouteFound(sprintf("%s not found", $this->request->url), 404);
    }


    public static function routes($name)
    {
        if (array_key_exists($name, (array) static::$nameList)) {
            return static::$nameList[$name];
        }
        return false;
    }
}
