<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_created_with_a_hash(): void
    {
        $password = Str::random(24);
        $this->artisan('isablue:admin')
            ->expectsQuestion('Nombre del administrador', 'Admin de prueba')
            ->expectsQuestion('Correo del administrador', 'admin@example.test')
            ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres)', $password)
            ->expectsQuestion('Repite la contraseña', $password)
            ->assertExitCode(0);
        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertNotSame($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_existing_customer_cannot_be_overwritten_or_promoted(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.test']);
        $hash = $user->password;
        $password = Str::random(24);
        $this->artisan('isablue:admin')
            ->expectsQuestion('Nombre del administrador', 'Otra persona')
            ->expectsQuestion('Correo del administrador', $user->email)
            ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres)', $password)
            ->expectsQuestion('Repite la contraseña', $password)
            ->assertExitCode(1);
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_mismatched_password_is_rejected(): void
    {
        $this->artisan('isablue:admin')
            ->expectsQuestion('Nombre del administrador', 'Admin de prueba')
            ->expectsQuestion('Correo del administrador', 'admin@example.test')
            ->expectsQuestion('Contraseña nueva (mínimo 12 caracteres)', Str::random(24))
            ->expectsQuestion('Repite la contraseña', Str::random(24))
            ->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }
}
