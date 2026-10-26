<?php

namespace Tests\Feature;

use App\Mail\StoreMessage;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\OutboundMail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\StockHold;
use App\Models\User;
use App\Services\Checkout;
use App\Services\Integrations;
use App\Services\MailOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class AccessOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function orderData()
    {
        Setting::updateOrCreate(['key' => 'demo_mode'], ['value' => '1']);
        $s = ShippingMethod::create(['name' => 'Prueba', 'price' => 10, 'active' => true]);

        return ['name' => 'Cliente de prueba', 'email' => 'cliente@example.test', 'phone' => '999000000', 'address' => 'Prueba', 'shipping_method_id' => $s->id];
    }

    private function product()
    {
        return Product::create(['name' => 'Prueba reserva', 'slug' => 'reserva-'.Str::random(6), 'sku' => Str::random(8), 'description' => 'Prueba', 'price' => 30, 'stock' => 2, 'active' => true, 'catalog_visible' => true]);
    }

    private function google($id = 'google123', $email = 'google@example.test', $verified = true)
    {
        config(['services.google.enabled' => true, 'services.google.client_id' => 'client', 'services.google.client_secret' => 'secret']);
        $g = (new \Laravel\Socialite\Two\User)->setRaw(['email_verified' => $verified])->map(['id' => $id, 'email' => $email, 'name' => 'Cliente Google']);
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($g);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_login_queues_one_notice_only_after_correct_password()
    {
        $u = User::factory()->create(['password' => Hash::make('correcta1234')]);
        $this->post('/ingresar', ['email' => $u->email, 'password' => 'incorrecta'])->assertSessionHasErrors();
        $this->assertSame(0, OutboundMail::count());
        $this->post('/ingresar', ['email' => $u->email, 'password' => 'correcta1234'])->assertRedirect();
        $this->assertSame(1, OutboundMail::where('kind', 'login')->count());
        $raw = DB::table('mail_outbox')->value('payload');
        $this->assertStringNotContainsString('Fecha y hora', $raw);
    }

    public function test_google_only_creates_customer_with_verified_email()
    {
        $this->google();
        $this->get('/auth/google/callback')->assertRedirect('/mi-cuenta');
        $u = User::where('google_id', 'google123')->firstOrFail();
        $this->assertSame('customer', $u->role);
        $this->assertNotNull($u->email_verified_at);
        $this->assertAuthenticatedAs($u);
        $this->assertSame(1, OutboundMail::where('kind', 'login')->count());
    }

    public function test_google_rejects_unverified_email()
    {
        $this->google(verified: false);
        $this->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_google_cannot_take_existing_account_by_email()
    {
        $u = User::factory()->create(['email' => 'google@example.test']);
        $this->google();
        $this->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertNull($u->fresh()->google_id);
    }

    public function test_logged_customer_can_link_google_without_admin_escalation()
    {
        $u = User::factory()->create(['email' => 'google@example.test']);
        $this->actingAs($u);
        $this->google();
        $this->get('/auth/google/callback')->assertRedirect('/mi-cuenta');
        $this->assertSame('google123', $u->fresh()->google_id);
        $this->assertSame('customer', $u->fresh()->role);
    }

    public function test_google_cannot_link_an_admin_account()
    {
        $u = User::factory()->create(['email' => 'google@example.test', 'role' => 'admin']);
        $this->actingAs($u);
        $this->google();
        $this->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertNull($u->fresh()->google_id);
    }

    public function test_unconfigured_google_is_honest_and_does_not_redirect_to_google()
    {
        config(['services.google.enabled' => false]);
        $this->get('/ingresar')->assertDontSee('href="/auth/google"', false)->assertDontSee('Próximamente');
        $this->get('/auth/google')->assertRedirect('/ingresar')->assertSessionHasErrors('google');
    }

    public function test_password_reset_token_expires_and_is_single_use()
    {
        config(['mail.default' => 'smtp']);
        $u = User::factory()->create();
        $this->post('/olvide-mi-contrasena', ['email' => $u->email])->assertSessionHasNoErrors();
        $m = OutboundMail::where('kind', 'reset')->firstOrFail();
        $token = basename(parse_url($m->payload['url'], PHP_URL_PATH));
        $data = ['email' => $u->email, 'token' => $token, 'password' => 'UnaNuevaClave1234', 'password_confirmation' => 'UnaNuevaClave1234'];
        $this->post('/restablecer-contrasena', $data)->assertRedirect('/ingresar');
        $this->assertTrue(Hash::check('UnaNuevaClave1234', $u->fresh()->password));
        $this->post('/restablecer-contrasena', $data)->assertSessionHasErrors('email');
        $token2 = Password::createToken($u);
        $this->travel(61)->minutes();
        $this->post('/restablecer-contrasena', array_merge($data, ['token' => $token2]))->assertSessionHasErrors('email');
    }

    public function test_reservation_blocks_second_buyer_and_expires_without_changing_physical_stock()
    {
        $p = $this->product();
        $d = $this->orderData();
        $c = app(Checkout::class);
        $a = $c->place([$p->id => 2], $d, null, (string) Str::uuid());
        $this->assertSame(2, $p->fresh()->stock);
        $this->assertSame(0, $p->fresh()->sellable_stock);
        try {
            $c->place([$p->id => 1], $d, null, (string) Str::uuid());
            $this->fail('Debe bloquear la reserva competidora');
        } catch (ValidationException $e) {
            $this->assertSame(1, Order::count());
        }
        $this->travel(31)->minutes();
        $this->assertSame(2, $p->fresh()->sellable_stock);
        $this->get('/pedido/'.$a->public_token)->assertSee('La reserva venció');
        $b = $c->place([$p->id => 2], $d, null, (string) Str::uuid());
        $this->expectException(ValidationException::class);
        $c->confirmPayment($a, null, 'Pago tardío');
    }

    public function test_payment_notice_and_stock_only_once_even_if_clicked_twice()
    {
        $p = $this->product();
        $c = app(Checkout::class);
        $o = $c->place([$p->id => 1], $this->orderData(), null, (string) Str::uuid());
        $this->assertTrue($c->confirmPayment($o, null, 'Yape'));
        $this->assertFalse($c->confirmPayment($o, null, 'Yape'));
        $this->assertSame(1, $p->fresh()->stock);
        $this->assertSame(0, StockHold::active()->count());
        $this->assertSame(1, OutboundMail::where('kind', 'payment')->count());
    }

    public function test_failed_email_retries_without_repeating_payment()
    {
        $p = $this->product();
        $c = app(Checkout::class);
        $o = $c->place([$p->id => 1], $this->orderData(), null, (string) Str::uuid());
        $c->confirmPayment($o, null, 'Yape');
        config(['mail.default' => 'smtp']);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('simulated SMTP failure'));
        $this->assertSame(0, MailOutbox::process(1));
        $m = OutboundMail::firstOrFail();
        $this->assertNotNull($m->last_error);
        $this->assertNull($m->sent_at);
        Mail::swap(new MailManager($this->app));
        Mail::fake();
        $this->travel(3)->minutes();
        $this->assertSame(1, MailOutbox::process(1));
        $this->assertNotNull($m->fresh()->sent_at);
        $this->assertSame(1, $p->fresh()->stock);
        $this->assertSame(0, MailOutbox::process(1));
        Mail::assertSent(StoreMessage::class, 1);
    }

    public function test_popup_absent_on_auth_and_integrations_are_admin_only()
    {
        $p = $this->product();
        Setting::create(['key' => 'popup_enabled', 'value' => '1']);
        Setting::create(['key' => 'popup_product_id', 'value' => $p->id]);
        foreach (['/ingresar', '/registro', '/olvide-mi-contrasena'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('data-popup data-delay', false);
        }
        $this->get('/admin/integraciones')->assertRedirect('/ingresar');
        $this->actingAs(User::factory()->create())->get('/admin/integraciones')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/integraciones')->assertOk();
    }

    public function test_password_change_invalidates_an_existing_session()
    {
        $u = User::factory()->create(['password' => Hash::make('ClaveAnterior1234')]);
        $this->post('/ingresar', ['email' => $u->email, 'password' => 'ClaveAnterior1234'])->assertRedirect();
        $this->get('/mi-cuenta')->assertOk();
        $u->update(['password' => Hash::make('ClaveNueva123456')]);
        $this->app['auth']->forgetGuards();
        $this->get('/mi-cuenta')->assertRedirect('/ingresar');
        $this->assertGuest();
    }

    public function test_disabled_smtp_overrides_environment_mailer()
    {
        IntegrationSetting::create(['key' => 'smtp', 'value' => ['enabled' => false]]);
        config(['mail.default' => 'smtp']);
        Integrations::apply();
        $this->assertFalse(Integrations::mailReady());
    }

    public function test_smtp_secrets_are_encrypted_and_never_rendered()
    {
        $this->actingAs($this->admin())->post('/admin/integraciones/smtp', ['host' => 'smtp.example.test', 'port' => 587, 'username' => 'test@example.test', 'password' => 'SecretSmtpNotRendered', 'from' => 'test@example.test', 'enabled' => 1])->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('SecretSmtpNotRendered', DB::table('integration_settings')->value('value'));
        $this->get('/admin/integraciones')->assertDontSee('SecretSmtpNotRendered');
    }
}
