<?php

namespace UmutcanGungormus\NamedRouteBinding;

use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;

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
        // If disabled, use default Laravel behavior
        if (!config('named-route-binding.enabled', true)) {
            return parent::dispatch($route, $controller, $method);
        }

        // Reorder route parameters to match method parameter names
        $this->reorderRouteParameters($route, $controller, $method);

        // Use Laravel's built-in dependency resolution
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(),
            $controller,
            $method
        );

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Reorder route parameters to match the controller method's parameter order.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return void
     */
    protected function reorderRouteParameters(Route $route, $controller, string $method): void
    {
        $routeParameters = $route->parameters();
        
        if (empty($routeParameters)) {
            return;
        }

        $methodParameters = $this->getMethodParameters($controller, $method);
        $reorderedParameters = [];
        $usedRouteParams = [];

        // First pass: match by name
        foreach ($methodParameters as $methodParam) {
            $paramName = $methodParam->getName();
            $matchedValue = $this->findMatchingRouteParameter($paramName, $routeParameters);
            
            if ($matchedValue !== null) {
                $reorderedParameters[$paramName] = $matchedValue['value'];
                $usedRouteParams[] = $matchedValue['key'];
            }
        }

        // Replace route parameters with reordered ones
        // First, forget all existing parameters
        foreach (array_keys($routeParameters) as $key) {
            $route->forgetParameter($key);
        }

        // Then set the reordered parameters
        foreach ($reorderedParameters as $key => $value) {
            $route->setParameter($key, $value);
        }

        // Add any remaining route parameters that weren't matched
        foreach ($routeParameters as $key => $value) {
            if (!in_array($key, $usedRouteParams) && !array_key_exists($key, $reorderedParameters)) {
                $route->setParameter($key, $value);
            }
        }
    }

    /**
     * Find a matching route parameter for the given method parameter name.
     *
     * @param  string  $paramName
     * @param  array  $routeParameters
     * @return array|null
     */
    protected function findMatchingRouteParameter(string $paramName, array $routeParameters): ?array
    {
        // 1. Exact match
        if (array_key_exists($paramName, $routeParameters)) {
            return ['key' => $paramName, 'value' => $routeParameters[$paramName]];
        }

        // 2. Try snake_case version
        $snakeCaseName = $this->toSnakeCase($paramName);
        if (array_key_exists($snakeCaseName, $routeParameters)) {
            return ['key' => $snakeCaseName, 'value' => $routeParameters[$snakeCaseName]];
        }

        // 3. Try camelCase version
        $camelCaseName = $this->toCamelCase($paramName);
        if (array_key_exists($camelCaseName, $routeParameters)) {
            return ['key' => $camelCaseName, 'value' => $routeParameters[$camelCaseName]];
        }

        return null;
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
