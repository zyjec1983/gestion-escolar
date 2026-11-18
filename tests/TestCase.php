<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Autentica al usuario indicado para la siguiente petición.
     *
     * Qué hace: olvida el hash de contraseña que guardó la petición anterior.
     * `AuthenticateSession` cierra la sesión cuando ese hash no corresponde al
     * usuario autenticado. Ese comportamiento es el correcto en producción,
     * pero en las pruebas impediría cambiar de usuario dentro de un mismo
     * método. Al olvidarlo, cada `actingAs` equivale a un inicio de sesión
     * nuevo, que es lo que la prueba pretende representar.
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->app['session']->forget('password_hash_web');

        return parent::actingAs($user, $guard);
    }
}
