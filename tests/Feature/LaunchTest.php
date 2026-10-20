<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LaunchTest extends TestCase
{
    use RefreshDatabase;

    private array $uploaded = [];

    protected function tearDown(): void
    {
        foreach ($this->uploaded as $path) {
            if (preg_match('~^/uploads/[a-f0-9-]+\.(png|jpg|jpeg|webp)$~', $path)) {
                @unlink(public_path(ltrim($path, '/')));
            }
        }
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_upload_multiple_promotions_hide_order_and_delete_them(): void
    {
        $this->actingAs($this->admin())->post('/admin/banners/imagenes', [
            'title' => 'Descuento de temporada', 'link' => '/catalogo?sale=1',
            'photos' => [UploadedFile::fake()->image('a.png', 900, 300), UploadedFile::fake()->image('b.png', 900, 300)],
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/banners');
        $banners = Banner::orderBy('sort_order')->get();
        $this->uploaded = $banners->pluck('image')->all();
        $this->assertCount(2, $banners);
        foreach ($this->uploaded as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')));
        }
        $this->get('/')->assertOk()->assertSee('hero-wide')->assertSee('hero-banner-image')
            ->assertSeeInOrder(['Descuento de temporada 1', 'Descuento de temporada 2']);
        $b = $banners[0];
        $this->put('/admin/banners/'.$b->id, [
            'title' => 'Oferta oculta', 'link' => '/catalogo', 'button' => 'Ver',
            'theme' => 'image', 'sort_order' => 10, 'active' => 0,
        ])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Oferta oculta')->assertSee('Descuento de temporada 2');
        $this->assertEquals($this->uploaded[0], $b->fresh()->image);
        $this->delete('/admin/banners/'.$b->id)->assertRedirect('/admin/banners');
        $this->assertDatabaseMissing('banners', ['id' => $b->id]);
        $this->assertFileExists(public_path(ltrim($this->uploaded[0], '/')));
    }

    public function test_banner_upload_rejects_executable_files_external_links_and_missing_images(): void
    {
        $this->actingAs($this->admin())->post('/admin/banners/imagenes', [
            'title' => 'Campaña', 'link' => '//evil.example',
            'photos' => [UploadedFile::fake()->create('shell.php', 5, 'application/x-php')],
        ])->assertSessionHasErrors(['link', 'photos.0']);
        $this->assertDatabaseCount('banners', 0);
        $this->post('/admin/banners', ['title' => 'Sin imagen', 'link' => '/catalogo', 'button' => 'Ver', 'theme' => 'image', 'sort_order' => 0])
            ->assertSessionHasErrors('photo');
    }

    public function test_customers_cannot_change_promotions(): void
    {
        $b = Banner::create(['title' => 'Visible']);
        $this->actingAs(User::factory()->create())->post('/admin/banners/imagenes', [])->assertForbidden();
        $this->delete('/admin/banners/'.$b->id)->assertForbidden();
        $this->assertDatabaseHas('banners', ['id' => $b->id]);
    }

    private function basket(): array
    {
        $p = Product::create(['name' => 'Producto verificado', 'slug' => 'producto', 'sku' => 'VERIFIED-TEST', 'description' => 'Producto ficticio de pruebas', 'price' => 20, 'stock' => 3, 'active' => true, 'catalog_visible' => true]);
        $s = ShippingMethod::create(['name' => 'Entrega verificada', 'price' => 5, 'active' => true]);
        $data = ['name' => 'Persona de prueba', 'email' => 'cliente@example.test', 'phone' => '900000002', 'address' => 'Dirección ficticia', 'shipping_method_id' => $s->id];

        return [$p, $s, $data];
    }

    public function test_real_checkout_requires_payment_details_before_creating_account_or_order(): void
    {
        [$p, $s, $data] = $this->basket();
        $key = (string) Str::uuid();
        $this->withSession(['cart' => [$p->id => 1], 'checkout_key' => $key])->get('/checkout')
            ->assertOk()->assertSee('El pago todavía no está disponible');
        $this->post('/checkout', $data + ['checkout_key' => $key, 'password' => 'soloParaPruebas123'])
            ->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_real_checkout_cannot_sell_unverified_items(): void
    {
        [$p, $s, $data] = $this->basket();
        Setting::create(['key' => 'yape_number', 'value' => '900000001']);
        Setting::create(['key' => 'yape_holder', 'value' => 'Prueba ficticia']);
        $p->update(['demo_data' => true]);
        $this->expectException(ValidationException::class);
        app(Checkout::class)->place([$p->id => 1], $data, null, (string) Str::uuid());
    }

    public function test_log_mailer_does_not_mark_confirmation_as_delivered(): void
    {
        [$p, $s, $data] = $this->basket();
        Setting::create(['key' => 'yape_number', 'value' => '900000001']);
        Setting::create(['key' => 'yape_holder', 'value' => 'Prueba ficticia']);
        config(['mail.default' => 'log']);
        Mail::fake();
        $key = (string) Str::uuid();
        $this->withSession(['cart' => [$p->id => 1], 'checkout_key' => $key])
            ->post('/checkout', $data + ['checkout_key' => $key, 'password' => 'soloParaPruebas123'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertNull($order->confirmation_sent_at);
        $this->assertFalse($order->is_demo);
        Mail::assertNothingSent();
    }
}
