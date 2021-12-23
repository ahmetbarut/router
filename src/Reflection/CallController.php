<?php

declare(strict_types=1);

namespace ahmetbarut\PhpRouter\Reflection;

use ahmetbarut\PhpRouter\Helper\Arr;
use ErrorException;
use Exception;
use ReflectionMethod;

class CallController
{
    /**
     * The domain name of the controllers to be loaded. Its source is \ahmetbarut\Router\Router $namespace.
     *
     * @var string
     * @source \ahmetbarut\Router\Router $namespace
     */
    private $namespace;

    /**
     * Name of the controller to be loaded.
     *
     * @var string
     */
    private $controller;

    /**
     * Contains the name of the method requested to be loaded.
     *
     * @var string
     */
    private $method;

    /**
     * It contains the parameters of the method to be loaded, if any.
     *
     * @var array
     */
    private $arguments;

    /**
     * \ahmetbarut\Reflection\Method is the installer.
     * The field name of the controllers when the class object is created,
     * sends the corresponding controller and method ("controller@method") and parameters, if any.
     *
     * @param string     $namespace
     * @param string     $action
     * @param array|null $methodParameters
     *
     * @throws ErrorException
     */
    public function __construct(string $namespace, string $action, ?array $methodParameters)
    {
        $this->setNampespace($namespace);
        $this->setController($action);
        $this->setMethod($action);
        $this->setArguments($methodParameters);
    }
    
    /**
     * Sets the controller name.
     *
     * @param string $action
     *
     * @throws ErrorException
     */
    private function setController(string $action): void
    {
        $this->controller = explode("@", $action)[0];
        if (!$this->controller) {
            throw new ErrorException("Controller name is not defined.");
        }
    }

    /**
     * Sets the method name.
     *
     * @param string $action
     *
     * @throws ErrorException
     */
    private function setMethod(string $action): void
    {
        $this->method = explode("@", $action)[1];
        if (!$this->method) {
            throw new ErrorException("Method name is not defined.");
        }
    }

    /**
     * Sets the method parameters.
     *
     * @param array|null $methodParameters
     *
     * @throws ErrorException
     */
    private function setArguments(?array $methodParameters): void
    {   
        if ($methodParameters) {
            $this->arguments = $methodParameters;
        }
    }

    /**
     * Sets the namespace.
     *
     * @param string $namespace
     *
     * @throws ErrorException
     */
    private function setNampespace(string $namespace): void
    {
        if (!$namespace) {
            throw new ErrorException("Namespace is not defined.");
        }
        $this->namespace = rtrim($namespace, '\\');
    }

    public function setParameters($parameters, $className, $methodName)
    {
        $counter = 0;
        foreach ($this->arguments as $key => $argument) {

            if ($key != $parameters[$counter]->getName()) {
                throw new ErrorException(sprintf("Expected parameter name (\$%s), given parameter name (\$%s) in %s::%s", $argument, $parameters[$counter]->getName(), $className, $methodName));
            }
            if (!$parameters[$counter]->getType()->isBuiltin()) {
                dd($parameters[$counter]->getType()->getName());
                $this->arguments[$key] = $parameters[$counter]->getName();
            }

            $counter++;
        }
    }
    
    public function initilazeParameter()
    {
        foreach($this->arguments as $key => $argument) {
            dd($this->arguments);
        }
    }
    
    public function dispatch()
    {
        $controller = $this->namespace . "\\" . $this->controller;
        $controller = new $controller();
        $method = new ReflectionMethod($controller, $this->method);

        $this->setParameters($method->getParameters(), $controller->getClassName(), $method->getName());
        
        return $method->invokeArgs($controller, $this->arguments);

    }

    
}
