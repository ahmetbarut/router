<?php

use ahmetbarut\PhpRouter\Exception\NotRouteFound;
use ahmetbarut\PhpRouter\Router\Router;
use symfony\Component\HttpFoundation\Request;

/**
 * Dizgedeki ilk karakteri yoksayar ve dizge 2. karakterden itibaren kabul edilir
 * Örneğin: "php-router" dizgesini verirsek, bize "hp-router" döndürür
 *
 * @param array|string $str
 * @return string|array
 */
function rm_first_letter(array|string $str)
{
    if (is_string($str)) {
        return substr($str, 1);
    }
    foreach ($str as $key => $string) {
        $str[$key] = substr($str[$key], 1);
    }
    return  $str;
}

/**
 * Router'e tanımlı isim ile routeri çağırmak ve parametre atamak için.
 *
 * @param string $name
 * @param array|null $parameters
 * @param boolean $absolute
 * @return string
 * @throws NotRouteFound
 * @throws ErrorException
 */
function path(string $name, ?array $parameters = [], bool $absolute = true): string
{
    $router = Router::routes($name);
    $uri = "";

    if (false === $router) {
        throw new NotRouteFound(sprintf("[%s] Not Found.", $name));
    }
    if (count((array) $router->parameters) !== count($parameters)) {
        throw new ErrorException(sprintf("Parametter is null, expected parameter %s, given parameter %s", count((array) $router->parameters), count($parameters)), 500);
    }
    if ($absolute) {
        $uri = Request::createFromGlobals()->getPathInfo() . '/' . str_replace($router->parameters, $parameters, $router->uri);
    } else {
        $uri = str_replace($router->parameters, $parameters, $router->uri);
    }
    return rtrim($uri, '/');
}
