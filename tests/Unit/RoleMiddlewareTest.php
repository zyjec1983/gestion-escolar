<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    private RoleMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoleMiddleware();
    }

    /** @test */
    public function unauthenticated_user_redirected_to_login()
    {
        $request = Request::create('/admin', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('ok');
        }, 'ADMIN');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    /** @test */
    public function user_with_matching_role_passes()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $request = Request::create('/admin', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('ok');
        }, 'ADMIN');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    /** @test */
    public function user_with_different_role_gets_403()
    {
        $docente = User::factory()->docente()->create();
        $this->actingAs($docente);

        $request = Request::create('/admin', 'GET');

        try {
            $this->middleware->handle($request, function () {
                return new Response('ok');
            }, 'ADMIN');
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    /** @test */
    public function matches_any_of_multiple_roles()
    {
        $docente = User::factory()->docente()->create();
        $this->actingAs($docente);

        $request = Request::create('/some-route', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('ok');
        }, 'ADMIN', 'DOCENTE');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function no_matching_role_among_multiple_gets_403()
    {
        $docente = User::factory()->docente()->create();
        $this->actingAs($docente);

        $request = Request::create('/some-route', 'GET');

        try {
            $this->middleware->handle($request, function () {
                return new Response('ok');
            }, 'ADMIN', 'REPRESENTANTE');
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    /** @test */
    public function route_integration_with_middleware()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Route::get('/_test_admin_route', function () {
            return 'ok';
        })->middleware('role:ADMIN');

        $response = $this->get('/_test_admin_route');
        $response->assertStatus(200);
        $response->assertSee('ok');
    }
}
