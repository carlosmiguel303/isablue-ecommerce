<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\StockHold;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Checkout;
use App\Services\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.connections.mysql.database') !== 'isablue_2026_test') {
            throw new \RuntimeException('Las pruebas solo pueden usar isablue_2026_test.');
        }
        Setting::create(['key' => 'yape_number', 'value' => '900000001']);
        Setting::create(['key' => 'yape_holder', 'value' => 'Titular ficticio de pruebas']);
    }

    private function product(array $attrs = []): Product
    {
        return Product::create(array_merge(['name' => 'Rompecabezas de prueba', 'slug' => 'p-'.Str::random(8), 'sku' => 'TEST', 'description' => 'Producto exclusivo de pruebas', 'price' => 45, 'stock' => 5, 'active' => true, 'catalog_visible' => true, 'age_min_months' => 36, 'age_max_months' => 71, 'age_source' => 'Ficha de pruebas'], $attrs));
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => 'admin'])->save();

        return $u;
    }

    private function delivery(): ShippingMethod
    {
        return ShippingMethod::create(['name' => 'Envío de prueba', 'price' => 10, 'active' => true]);
    }

    private function data($shipping): array
    {
        return ['name' => 'Cliente Prueba', 'email' => 'prueba@example.test', 'phone' => '999999999', 'address' => 'Dirección de prueba', 'shipping_method_id' => $shipping->id];
    }

    public function test_home_and_catalog_use_database(): void
    {
        $p = $this->product(['featured' => true]);
        $this->get('/')->assertOk()->assertSee($p->name);
        $this->get('/catalogo')->assertOk()->assertSee($p->name);
        $p->update(['name' => 'Nombre modificado']);
        $this->get('/catalogo')->assertSee('Nombre modificado')->assertDontSee('Rompecabezas de prueba');
    }

    public function test_filters_combine_age_category_price_availability_and_sale(): void
    {
        $c = Category::create(['name' => 'Puzzle', 'slug' => 'puzzle']);
        $match = $this->product(['name' => 'Coincidencia precisa', 'compare_price' => 60]);
        $match->categories()->attach($c);
        $this->product(['name' => 'Fuera de edad', 'age_min_months' => 0, 'age_max_months' => 35])->categories()->attach($c);
        $this->product(['name' => 'Sin stock', 'stock' => 0])->categories()->attach($c);
        $this->product(['name' => 'Muy caro', 'price' => 180])->categories()->attach($c);
        $this->get('/catalogo?age=3-4&category[]='.$c->id.'&min=30&max=100&availability=available&sale=1')->assertOk()->assertSee('Coincidencia precisa')->assertDontSee('Fuera de edad')->assertDontSee('Sin stock')->assertDontSee('Muy caro');
    }

    public function test_age_overlap_and_unknown_age(): void
    {
        $this->product(['name' => 'Edades solapadas', 'age_min_months' => 24, 'age_max_months' => 48]);
        $this->product(['name' => 'Sin edad documentada', 'age_min_months' => null, 'age_max_months' => null]);
        $this->get('/catalogo?age=0-2')->assertSee('Edades solapadas')->assertDontSee('Sin edad documentada');
        $this->get('/catalogo?age=3-4')->assertSee('Edades solapadas');
        $this->get('/catalogo?age=5')->assertDontSee('Edades solapadas');
    }

    public function test_hidden_products_are_not_public_or_searchable(): void
    {
        $p = $this->product(['name' => 'Oculto secreto', 'catalog_visible' => false]);
        $this->get('/catalogo')->assertDontSee($p->name);
        $this->get('/juguete/'.$p->slug)->assertNotFound();
        $this->get('/buscar/sugerencias?q=Oculto')->assertExactJson([]);
    }

    public function test_customer_cannot_access_admin_or_mutate_products(): void
    {
        $p = $this->product();
        $this->get('/admin')->assertRedirect('/ingresar');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->put('/admin/productos/'.$p->id, ['name' => 'Hack'])->assertForbidden();
    }

    public function test_spring_bcrypt_password_is_accepted_and_rehashed(): void
    {
        $hash = chr(36).'2a'.chr(36).substr(Hash::make('Original123'), 4);
        $u = User::factory()->create(['email' => 'spring@example.test', 'password' => $hash]);
        $this->post('/ingresar', ['email' => $u->email, 'password' => 'Original123'])->assertRedirect('/mi-cuenta');
        $this->assertAuthenticatedAs($u);
        $this->assertTrue(password_verify('Original123', $u->fresh()->password));
    }

    public function test_login_throttles_wrong_passwords(): void
    {
        $u = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/ingresar', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }$this->post('/ingresar', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors(['email' => 'Demasiados intentos. Espera un minuto.']);
    }

    public function test_admin_can_save_product_and_audited_stock(): void
    {
        $c = Category::create(['name' => 'Dinámica', 'slug' => 'dinamica']);
        $this->actingAs($this->admin())->post('/admin/productos', ['name' => 'Juguete nuevo', 'sku' => 'NEW', 'description' => 'Descripción real', 'price' => 30, 'active' => 1, 'catalog_visible' => 1, 'categories' => [$c->id]])->assertRedirect();
        $p = Product::where('sku', 'NEW')->firstOrFail();
        $this->assertNull($p->stock);
        $this->post('/admin/productos/'.$p->id.'/stock', ['stock' => 8, 'reason' => 'Conteo físico'])->assertRedirect();
        $this->assertEquals(8, $p->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'change' => 8, 'after' => 8]);
        $this->get('/catalogo?availability=available')->assertSee('Juguete nuevo');
    }

    public function test_admin_categories_banners_and_shipping_persist(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/categorias', ['name' => 'Música', 'slug' => 'musica', 'sort_order' => 1, 'active' => 1])->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'musica']);
        $this->post('/admin/banners', ['title' => 'Banner real', 'button' => 'Explorar', 'link' => '/catalogo', 'theme' => 'main', 'sort_order' => 0, 'active' => 1])->assertRedirect();
        $this->get('/')->assertSee('Banner real');
        $this->post('/admin/envios', ['name' => 'Entrega configurada', 'price' => 12, 'active' => 1])->assertRedirect();
        $this->assertDatabaseHas('shipping_methods', ['name' => 'Entrega configurada', 'price' => 12]);
    }

    public function test_unknown_age_stage_and_invalid_upload_are_rejected(): void
    {
        $c = Category::create(['name' => 'Puzzle', 'slug' => 'puzzle']);
        $this->actingAs($this->admin())->post('/admin/productos', ['name' => 'Mal dato', 'sku' => 'X', 'description' => 'x', 'price' => 30, 'categories' => [$c->id], 'age_from' => '9'])->assertSessionHasErrors('age_from');
        $this->post('/admin/banners', ['title' => 'X', 'button' => 'x', 'link' => '//evil.test', 'theme' => 'main', 'sort_order' => 0, 'photo' => UploadedFile::fake()->create('code.php', 10, 'application/x-php')])->assertSessionHasErrors(['link', 'photo']);
    }

    public function test_cart_quantity_and_unknown_stock_are_enforced(): void
    {
        $p = $this->product();
        $this->postJson('/carrito', ['product_id' => $p->id, 'quantity' => 2])->assertOk()->assertJson(['count' => 2]);
        $this->postJson('/carrito', ['product_id' => $p->id, 'quantity' => 4])->assertUnprocessable();
        $this->patch('/carrito/'.$p->id, ['quantity' => 1])->assertRedirect();
        $this->get('/carrito')->assertSee('S/ 45.00');
        $this->patch('/carrito/'.$p->id, ['quantity' => 0]);
        $this->assertEmpty(session('cart'));
        $p->update(['stock' => null]);
        $this->postJson('/carrito', ['product_id' => $p->id, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_guest_checkout_prices_are_authoritative_and_replay_is_idempotent(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::fake();
        $p = $this->product();
        $shipping = $this->delivery();
        $key = (string) Str::uuid();
        $payload = $this->data($shipping) + ['checkout_key' => $key, 'total' => 1, 'password' => 'secreto123'];
        $this->withSession(['cart' => [$p->id => 2], 'checkout_key' => $key])->post('/checkout', $payload)->assertRedirect();
        $o = Order::firstOrFail();
        $this->assertEquals(100, $o->total);
        $this->assertEquals(5, $p->fresh()->stock, 'El stock no se descuenta hasta confirmar el Yape.');
        $this->assertEquals('pending', $o->payment_status);
        $this->assertNotNull($o->user_id, 'El comprador nuevo obtiene una cuenta.');
        $this->assertAuthenticated();
        $this->assertTrue(password_verify('secreto123', User::find($o->user_id)->password));
        $this->post('/checkout', $payload)->assertRedirect('/pedido/'.$o->public_token);
        $this->assertEquals(1, Order::count());
        $this->assertEquals(0, StockMovement::count());
        Mail::assertSent(OrderConfirmation::class, 1);
        $this->withSession(['cart' => [$p->id => 1]])->get('/checkout')->assertOk();
        $this->assertNotEquals($key, session('checkout_key'));
    }

    public function test_insufficient_stock_rolls_back_whole_order(): void
    {
        $p = $this->product(['stock' => 1]);
        $s = $this->delivery();
        try {
            app(Checkout::class)->place([$p->id => 2], $this->data($s), null, (string) Str::uuid());
            $this->fail('Expected validation');
        } catch (ValidationException $e) {
            $this->assertEquals(0, Order::count());
            $this->assertEquals(1, $p->fresh()->stock);
        }
    }

    public function test_cancellation_releases_stock_exactly_once(): void
    {
        $p = $this->product();
        $s = $this->delivery();
        $checkout = app(Checkout::class);
        $o = $checkout->place([$p->id => 2], $this->data($s), null, (string) Str::uuid());
        $u = $this->admin();
        $checkout->confirmPayment($o, $u->id, 'Yape confirmado');
        $this->assertEquals(3, $p->fresh()->stock);
        $checkout->transition($o, 'cancelled', $u->id);
        $checkout->transition($o, 'cancelled', $u->id);
        $this->assertEquals(5, $p->fresh()->stock);
        $this->assertEquals(2, StockMovement::count());
    }

    public function test_verified_payment_deduplicates_and_checks_amount(): void
    {
        $p = $this->product();
        $s = $this->delivery();
        $o = app(Checkout::class)->place([$p->id => 1], $this->data($s), null, (string) Str::uuid());
        $recorder = app(PaymentRecorder::class);
        $this->assertTrue($recorder->recordVerified('test', 'evt1', $o->id, 5500, 'PEN', []));
        $this->assertFalse($recorder->recordVerified('test', 'evt1', $o->id, 5500, 'PEN', []));
        $this->assertEquals('paid', $o->fresh()->payment_status);
        $this->expectException(ValidationException::class);
        $recorder->recordVerified('test', 'evt2', $o->id, 1, 'PEN', []);
    }

    public function test_product_html_is_escaped(): void
    {
        $p = $this->product(['name' => '<script>alert(1)</script>']);
        $this->get('/juguete/'.$p->slug)->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin());
        $p = $this->product();
        foreach (['/admin', '/admin/reportes', '/admin/reportes?periodo=hoy', '/admin/reportes?periodo=rango&desde=2026-01-01&hasta=2026-12-31', '/admin/inventario?ver=bajo', '/admin/inventario?ver=sin-ventas', '/admin/pedidos?tab=preparar', '/admin/productos', '/admin/productos/nuevo', '/admin/productos/'.$p->id.'/editar', '/admin/categorias', '/admin/categorias/nueva', '/admin/banners', '/admin/banners/nuevo', '/admin/inventario', '/admin/pedidos', '/admin/configuracion'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_demo_orders_are_labelled_and_excluded_from_sales(): void
    {
        Setting::create(['key' => 'demo_mode', 'value' => '1']);
        $p = $this->product(['demo_data' => true]);
        $shipping = $this->delivery();
        $order = app(Checkout::class)->place([$p->id => 1], $this->data($shipping), null, (string) Str::uuid());
        $this->assertTrue($order->is_demo);
        $order->update(['payment_status' => 'paid']);
        $this->get('/pedido/'.$order->public_token)->assertOk()->assertSee('Pedido de prueba');
        Setting::where('key', 'demo_mode')->update(['value' => '0']);
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertViewHas('sales', fn ($sales) => (float) $sales === 0.0);
        Setting::where('key', 'demo_mode')->update(['value' => '1']);
        $p->update(['demo_data' => false]);
        $shipping->update(['demo_data' => true]);
        $second = app(Checkout::class)->place([$p->id => 1], $this->data($shipping), null, (string) Str::uuid());
        $this->assertTrue($second->is_demo);
    }

    public function test_demo_notice_cannot_be_disabled_with_active_demo_data(): void
    {
        Setting::create(['key' => 'demo_mode', 'value' => '1']);
        $p = $this->product(['demo_data' => true]);
        $this->get('/')->assertOk()->assertSee('Modo de prueba');
        $this->actingAs($this->admin())->post('/admin/configuracion', ['demo_mode' => 0])->assertSessionHasErrors('demo_mode');
        $p->update(['demo_data' => false]);
        $this->post('/admin/configuracion', ['demo_mode' => 0])->assertRedirect();
        $this->assertDatabaseHas('settings', ['key' => 'demo_mode', 'value' => '0']);
    }

    public function test_stock_is_deducted_only_when_yape_is_confirmed(): void
    {
        $p = $this->product(['stock' => 5]);
        $s = $this->delivery();
        $o = app(Checkout::class)->place([$p->id => 2], $this->data($s), null, (string) Str::uuid());
        $this->assertEquals(5, $p->fresh()->stock);
        $this->actingAs($this->admin())->get('/admin/pedidos?tab=yape')->assertOk()->assertSee($o->reference);
        $this->get('/admin/pedidos/'.$o->id)->assertOk()->assertSee('ya yapeó');
        // Sin pago no puede avanzar.
        $this->post('/admin/pedidos/'.$o->id.'/estado', ['status' => 'processing'])->assertSessionHasErrors('status');
        $this->post('/admin/pedidos/'.$o->id.'/pago', ['payment_reference' => '123456'])->assertRedirect();
        $o->refresh();
        $this->assertEquals('paid', $o->payment_status);
        $this->assertEquals('confirmed', $o->status);
        $this->assertNotNull($o->paid_at);
        $this->assertEquals('123456', $o->payment_reference);
        $this->assertEquals(3, $p->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'order_id' => $o->id, 'change' => -2]);
        // Confirmar dos veces no descuenta de nuevo.
        $this->post('/admin/pedidos/'.$o->id.'/pago')->assertRedirect();
        $this->assertEquals(3, $p->fresh()->stock);
        foreach (['processing', 'shipped', 'completed'] as $status) {
            $this->post('/admin/pedidos/'.$o->id.'/estado', ['status' => $status])->assertSessionHasNoErrors();
        }
        $this->assertEquals('completed', $o->fresh()->status);
        $this->get('/admin/reportes?prueba=1')->assertOk()->assertSee($p->name)->assertViewHas('units', 2);
        $this->get('/admin/reportes?exportar=csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get('/admin?prueba=1')->assertOk()->assertViewHas('sales', fn ($sales) => (float) $sales === 100.0);
        $this->get('/admin/inventario')->assertOk()->assertSee($p->name);
    }

    public function test_bulk_yape_confirmation_and_shortage(): void
    {
        $p = $this->product(['stock' => 4]);
        $s = $this->delivery();
        $c = app(Checkout::class);
        $a = $c->place([$p->id => 2], $this->data($s), null, (string) Str::uuid());
        $b = $c->place([$p->id => 2], $this->data($s), null, (string) Str::uuid());
        StockHold::whereIn('order_id', [$a->id, $b->id])->update(['released_at' => now()]);
        $p->update(['stock' => 3]); // Dos pedidos antiguos sin reserva, con un conteo físico posterior inferior.
        $this->actingAs($this->admin())->post('/admin/pedidos/confirmar-yape', ['ids' => [$a->id, $b->id]])->assertSessionHasErrors();
        $this->assertEquals('paid', $a->fresh()->payment_status);
        $this->assertEquals('pending', $b->fresh()->payment_status, 'Sin stock suficiente el segundo queda en espera.');
        $this->assertEquals(1, $p->fresh()->stock);
        $this->post('/admin/pedidos/'.$b->id.'/estado', ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals(1, $p->fresh()->stock, 'Cancelar un pedido no pagado no toca el stock.');
    }

    public function test_customer_uploads_yape_voucher(): void
    {
        $p = $this->product();
        $o = app(Checkout::class)->place([$p->id => 1], $this->data($this->delivery()), null, (string) Str::uuid());
        $this->get('/pedido/'.$o->public_token)->assertOk()->assertSee('Yapea');
        $this->post('/pedido/'.$o->public_token.'/comprobante', ['voucher' => UploadedFile::fake()->image('yape.png'), 'payment_reference' => '998877'])->assertRedirect();
        $o->refresh();
        $this->assertEquals('review', $o->payment_status);
        $this->assertEquals('998877', $o->payment_reference);
        $this->assertNotNull($o->voucher_path);
        $this->get('/admin/pedidos/'.$o->id.'/comprobante')->assertRedirect('/ingresar');
        $this->actingAs($this->admin())->get('/admin/pedidos/'.$o->id.'/comprobante')->assertOk();
        Storage::disk('local')->delete($o->voucher_path);
    }

    public function test_store_pages_and_mini_cart(): void
    {
        $p = $this->product();
        foreach (['/nosotros', '/contacto', '/como-comprar', '/carrito', '/checkout'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->postJson('/carrito', ['product_id' => $p->id, 'quantity' => 1])->assertOk()->assertJsonPath('lines.0.name', $p->name);
        $this->getJson('/carrito/mini')->assertOk()->assertJsonPath('count', 1);
        $this->post('/carrito', ['product_id' => $p->id, 'quantity' => 1, 'buy_now' => 1])->assertRedirect('/checkout');
    }

    public function test_promo_bar_and_popup_settings(): void
    {
        $p = $this->product(['compare_price' => 60]);
        $this->actingAs($this->admin())->post('/admin/configuracion', ['demo_mode' => 1, 'promo_enabled_present' => 1, 'promo_enabled' => 1, 'promo_text' => 'Cyber Isablue', 'promo_ends_at' => now()->addDay()->format('Y-m-d\TH:i'), 'popup_enabled_present' => 1, 'popup_enabled' => 1, 'popup_product_id' => $p->id, 'free_shipping_from' => 50])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('Cyber Isablue')->assertSee('data-countdown', false)->assertSee('data-popup', false)->assertSee('-25%');
        $o = app(Checkout::class)->place([$p->id => 2], $this->data($this->delivery()), null, (string) Str::uuid());
        $this->assertEquals(0, (float) $o->shipping_total, 'Envío gratis sobre el mínimo configurado.');
    }

    public function test_checkout_requires_password_of_existing_account(): void
    {
        $p = $this->product();
        $shipping = $this->delivery();
        $u = User::factory()->create(['email' => 'prueba@example.test', 'password' => Hash::make('correcta123')]);
        $key = (string) Str::uuid();
        $this->withSession(['cart' => [$p->id => 1], 'checkout_key' => $key])->post('/checkout', $this->data($shipping) + ['checkout_key' => $key])->assertSessionHasErrors('password');
        $this->post('/checkout', $this->data($shipping) + ['checkout_key' => $key, 'password' => 'incorrecta1'])->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertEquals(0, Order::count());
        $this->post('/checkout', $this->data($shipping) + ['checkout_key' => $key, 'password' => 'correcta123'])->assertRedirect();
        $this->assertEquals($u->id, Order::firstOrFail()->user_id);
        $this->get('/mi-cuenta')->assertOk()->assertSee(Order::firstOrFail()->reference);
        $this->flushSession();
        $this->actingAs($this->admin())->getJson('/admin/pedidos/ultimo')->assertOk()->assertJsonPath('awaiting', 1);
    }

    public function test_age_stages_and_specifications_are_saved_and_shown(): void
    {
        $c = Category::create(['name' => 'Bebés', 'slug' => 'bebes', 'active' => true]);
        $this->actingAs($this->admin())->post('/admin/productos', ['name' => 'Sonajero suave', 'sku' => 'SON', 'description' => 'Sonajero de tela', 'specifications' => "Material: algodón
Medidas: 12 cm", 'price' => 30, 'stock' => 3, 'active' => '1', 'catalog_visible' => '1', 'categories' => [$c->id], 'age_from' => '3-11m', 'age_to' => '1'])->assertSessionHasNoErrors();
        $p = Product::where('sku', 'SON')->firstOrFail();
        $this->assertSame([3, 23], [$p->age_min_months, $p->age_max_months]);
        $this->assertSame('3 meses a 1 año', $p->age_label);
        $this->get('/juguete/'.$p->slug)->assertOk()->assertSee('Especificaciones')->assertSee('algodón')->assertSee('12 cm');
        $this->get('/catalogo?age=1')->assertOk()->assertSee('Sonajero suave');
        $this->get('/catalogo?age=0-3m')->assertOk()->assertDontSee('Sonajero suave');
        $this->get('/catalogo?age=3')->assertOk()->assertDontSee('Sonajero suave');
        $this->assertSame('2 años', $this->product(['age_min_months' => 24, 'age_max_months' => 35])->age_label);
    }

    public function test_google_account_gets_clear_message_when_using_password(): void
    {
        $u = User::factory()->create(['email' => 'cliente@example.test']);
        $u->forceFill(['password' => null, 'google_id' => 'g-123', 'auth_provider' => 'google'])->save();
        $this->post('/ingresar', ['email' => 'cliente@example.test', 'password' => 'otra-clave-123'])->assertSessionHasErrors(['email' => 'Esta cuenta utiliza Google para iniciar sesión. Selecciona «Continuar con Google» en la parte inferior.']);
        $this->post('/registro', ['name' => 'Otro', 'email' => 'Cliente@Example.test', 'password' => 'clave-segura', 'password_confirmation' => 'clave-segura'])->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'cliente@example.test')->count());
        $linked = User::factory()->create(['email' => 'mixto@example.test', 'password' => Hash::make('clave-local-1')]);
        $linked->forceFill(['google_id' => 'g-456'])->save();
        $this->post('/ingresar', ['email' => 'mixto@example.test', 'password' => 'clave-local-1'])->assertRedirect('/mi-cuenta');
    }

    public function test_customer_can_choose_cash_on_delivery_and_sees_instructions(): void
    {
        $p = $this->product();
        $shipping = $this->delivery();
        $key = (string) Str::uuid();
        $payload = $this->data($shipping) + ['checkout_key' => $key, 'password' => 'secreto123', 'payment_method' => 'contraentrega'];
        $this->withSession(['cart' => [$p->id => 1], 'checkout_key' => $key])->post('/checkout', $payload)->assertRedirect();
        $o = Order::firstOrFail();
        $this->assertSame('contraentrega', $o->payment_method);
        $this->assertTrue($o->reserved_until->greaterThan(now()->addHours(23)));
        $this->get('/pedido/'.$o->public_token)->assertOk()->assertSee('Contraentrega')->assertDontSee('Solo falta tu Yape');
        $this->post('/checkout', $this->data($shipping) + ['checkout_key' => (string) Str::uuid(), 'password' => 'secreto123', 'payment_method' => 'bitcoin'])->assertSessionHasErrors('payment_method');
    }

    public function test_checkout_lists_all_payment_methods_with_yape_first(): void
    {
        $p = $this->product();
        $this->delivery();
        $html = $this->withSession(['cart' => [$p->id => 1]])->get('/checkout')->assertOk()->assertSee('Transferencia bancaria')->assertSee('Contraentrega')->assertSee('Efectivo (monto exacto)')->getContent();
        $this->assertLessThan(strpos($html, 'value="transferencia"'), strpos($html, 'value="yape"'));
    }

    public function test_blog_shows_only_published_posts_and_admin_can_publish(): void
    {
        $this->actingAs($this->admin())->post('/admin/blog', ['title' => 'Juegos para 1 año', 'excerpt' => 'Ideas simples', 'body' => "Primer párrafo.\n\nSegundo párrafo.", 'published' => '1'])->assertRedirect('/admin/blog');
        $this->post('/admin/blog', ['title' => 'Borrador secreto', 'body' => 'Aún no'])->assertRedirect('/admin/blog');
        $post = \App\Models\Post::where('title', 'Juegos para 1 año')->firstOrFail();
        $this->get('/blog')->assertOk()->assertSee('Juegos para 1 año')->assertDontSee('Borrador secreto');
        $this->get('/blog/'.$post->slug)->assertOk()->assertSee('Segundo párrafo.');
        $draft = \App\Models\Post::where('title', 'Borrador secreto')->firstOrFail();
        $this->get('/blog/'.$draft->slug)->assertNotFound();
        $this->get('/')->assertSee('href="/blog"', false);
    }

    public function test_admin_sets_season_theme_and_rotating_announcements(): void
    {
        Setting::updateOrCreate(['key' => 'season_theme'], ['value' => 'navidad']);
        Setting::updateOrCreate(['key' => 'shipping_bar_text'], ['value' => "Envíos a todo Lima\nPaga con Yape"]);
        $this->get('/')->assertOk()->assertSee('theme-navidad')->assertSee('Envíos a todo Lima')->assertSee('Paga con Yape')->assertSee('🎄');
        Setting::updateOrCreate(['key' => 'season_theme'], ['value' => 'inventado']);
        $this->get('/')->assertOk()->assertSee('theme-normal');
        $this->actingAs($this->admin())->get('/admin/configuracion')->assertOk()->assertSee('Tema de temporada');
    }
}
