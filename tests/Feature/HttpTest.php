<?php

namespace Tests\Feature;

use App\Models\Institucion;
use Tests\TestCase;

class HttpTest extends TestCase
{
    /** @test */
    public function welcome_page_returns_200()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_admin_can_access_dashboard()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_access_clases()
    {
        $docente = \App\Models\User::factory()->docente()->create();
        $this->actingAs($docente);

        $response = $this->get('/clases');
        $response->assertStatus(200);
    }
}
