<?php

namespace UmutcanGungormus\NamedRouteBinding\Tests;

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use UmutcanGungormus\NamedRouteBinding\NamedRouteBindingServiceProvider;

class NamedRouteBindingTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            NamedRouteBindingServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {
        $router->get('/users/{user}/posts/{post}', [TestController::class, 'orderedParams']);
        $router->get('/users/{user}/posts/{post}/reversed', [TestController::class, 'reversedParams']);
        $router->get('/users/{user_id}', [TestController::class, 'snakeCaseParam']);
        $router->get('/items/{item}', [TestController::class, 'withRequest']);
        $router->get('/optional/{required}', [TestController::class, 'withDefault']);
        $router->get('/nullable/{value}', [TestController::class, 'withNullable']);
    }

    /** @test */
    public function it_binds_parameters_in_order(): void
    {
        $response = $this->get('/users/123/posts/456');

        $response->assertOk();
        $response->assertJson([
            'user' => '123',
            'post' => '456',
        ]);
    }

    /** @test */
    public function it_binds_parameters_by_name_regardless_of_order(): void
    {
        $response = $this->get('/users/123/posts/456/reversed');

        $response->assertOk();
        $response->assertJson([
            'post' => '456',
            'user' => '123',
        ]);
    }

    /** @test */
    public function it_converts_snake_case_to_camel_case(): void
    {
        $response = $this->get('/users/789');

        $response->assertOk();
        $response->assertJson([
            'userId' => '789',
        ]);
    }

    /** @test */
    public function it_injects_request_along_with_route_parameters(): void
    {
        $response = $this->get('/items/abc?foo=bar');

        $response->assertOk();
        $response->assertJson([
            'item' => 'abc',
            'hasRequest' => true,
            'query' => 'bar',
        ]);
    }

    /** @test */
    public function it_uses_default_values_for_missing_parameters(): void
    {
        $response = $this->get('/optional/test');

        $response->assertOk();
        $response->assertJson([
            'required' => 'test',
            'optional' => 'default_value',
        ]);
    }

    /** @test */
    public function it_handles_nullable_parameters(): void
    {
        $response = $this->get('/nullable/test');

        $response->assertOk();
        $response->assertJson([
            'value' => 'test',
            'nullableParam' => null,
        ]);
    }

    /** @test */
    public function it_can_be_disabled_via_config(): void
    {
        config(['named-route-binding.enabled' => false]);

        // When disabled, parameters should be bound in order (default Laravel behavior)
        $response = $this->get('/users/123/posts/456');

        $response->assertOk();
        $response->assertJson([
            'user' => '123',
            'post' => '456',
        ]);
    }
}

class TestController
{
    public function orderedParams($user, $post): array
    {
        return [
            'user' => $user,
            'post' => $post,
        ];
    }

    public function reversedParams($post, $user): array
    {
        return [
            'post' => $post,
            'user' => $user,
        ];
    }

    public function snakeCaseParam($userId): array
    {
        return [
            'userId' => $userId,
        ];
    }

    public function withRequest($item, Request $request): array
    {
        return [
            'item' => $item,
            'hasRequest' => $request instanceof Request,
            'query' => $request->query('foo'),
        ];
    }

    public function withDefault($required, $optional = 'default_value'): array
    {
        return [
            'required' => $required,
            'optional' => $optional,
        ];
    }

    public function withNullable($value, ?string $nullableParam): array
    {
        return [
            'value' => $value,
            'nullableParam' => $nullableParam,
        ];
    }
}

