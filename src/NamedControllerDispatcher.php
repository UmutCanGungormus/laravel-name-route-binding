<?php

namespace UmutcanGungormus\NamedRouteBinding;

use Illuminate\Container\Container;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionParameter;

class NamedControllerDispatcher extends ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method)
    {
        if (!config('named-route-binding.enabled', true)) {
            return parent::dispatch($route, $controller, $method);
        }

        $parameters = $this->resolveNamedParameters($route, $controller, $method);

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Resolve parameters by matching route parameter names to method parameter names.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return array
     */
    protected function resolveNamedParameters(Route $route, $controller, string $method): array
    {
        $routeParameters = $route->parameters();
        $methodParameters = $this->getMethodParameters($controller, $method);

        $resolved = [];

        foreach ($methodParameters as $parameter) {
            $resolved[] = $this->resolveParameter($parameter, $routeParameters, $route);
        }

        return $resolved;
    }

    /**
     * Get the parameters for a controller method.
     *
     * @param  mixed  $controller
     * @param  string  $method
     * @return ReflectionParameter[]
     */
    protected function getMethodParameters($controller, string $method): array
    {
        $reflection = new ReflectionMethod($controller, $method);
        
        return $reflection->getParameters();
    }

    /**
     * Resolve a single parameter.
     *
     * @param  ReflectionParameter  $parameter
     * @param  array  $routeParameters
     * @param  \Illuminate\Routing\Route  $route
     * @return mixed
     */
    protected function resolveParameter(ReflectionParameter $parameter, array $routeParameters, Route $route): mixed
    {
        $parameterName = $parameter->getName();
        $parameterType = $parameter->getType();

        // 1. Look for exact name match in route parameters
        if (array_key_exists($parameterName, $routeParameters)) {
            return $routeParameters[$parameterName];
        }

        // 2. Try snake_case / camelCase conversion
        $snakeCaseName = $this->toSnakeCase($parameterName);
        if (array_key_exists($snakeCaseName, $routeParameters)) {
            return $routeParameters[$snakeCaseName];
        }

        $camelCaseName = $this->toCamelCase($parameterName);
        if (array_key_exists($camelCaseName, $routeParameters)) {
            return $routeParameters[$camelCaseName];
        }

        // 3. If type-hinted class, resolve from container
        if ($parameterType && !$parameterType->isBuiltin()) {
            $typeName = $parameterType->getName();
            
            // Check if it's a Request class
            if (is_subclass_of($typeName, \Illuminate\Http\Request::class) || $typeName === \Illuminate\Http\Request::class) {
                return $this->container->make(\Illuminate\Http\Request::class);
            }

            // Resolve other dependencies from container
            if ($this->container->bound($typeName) || class_exists($typeName)) {
                try {
                    return $this->container->make($typeName);
                } catch (\Exception $e) {
                    // If container can't resolve, fall back to default value
                }
            }
        }

        // 4. Use default value if available
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // 5. Return null if nullable
        if ($parameter->allowsNull()) {
            return null;
        }

        // 6. If none of the above, throw exception
        throw new \InvalidArgumentException(
            "Unable to resolve parameter [{$parameterName}] for controller method. " .
            "Available route parameters: " . implode(', ', array_keys($routeParameters))
        );
    }

    /**
     * Convert string to snake_case.
     *
     * @param  string  $value
     * @return string
     */
    protected function toSnakeCase(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    /**
     * Convert string to camelCase.
     *
     * @param  string  $value
     * @return string
     */
    protected function toCamelCase(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value))));
    }
}

