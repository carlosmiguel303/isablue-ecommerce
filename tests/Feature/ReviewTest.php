<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        return Product::create(['name' => 'Bloques ABC', 'slug' => 'bloques-'.Str::random(6), 'sku' => 'T', 'description' => 'Bloques de prueba', 'price' => 50, 'stock' => 5, 'active' => true, 'catalog_visible' => true, 'age_min_months' => 24, 'age_max_months' => 35]);
    }

    private function buyer(Product $product, string $status = 'completed', string $name = 'Andrea Rojas Pérez'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $order = Order::create(['reference' => 'ISA-'.Str::random(6), 'public_token' => (string) Str::uuid(), 'user_id' => $user->id, 'customer_name' => $name, 'status' => $status, 'payment_status' => 'paid', 'total' => 50]);
        $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => 50, 'total' => 50]);

        return $user;
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => 'admin'])->save();

        return $u;
    }

    private function review(array $extra = []): array
    {
        return array_merge(['rating' => 5, 'comment' => 'Llegó rapidísimo y a mi sobrino le encantó.', 'district' => 'Los Olivos', 'consent' => '1'], $extra);
    }

    public function test_only_buyers_with_shipped_or_delivered_orders_can_review(): void
    {
        $p = $this->product();
        $this->get('/juguete/'.$p->slug.'/resena')->assertRedirect('/ingresar');
        $this->actingAs(User::factory()->create())->get('/juguete/'.$p->slug.'/resena')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/juguete/'.$p->slug.'/resena', $this->review())->assertForbidden();
        $this->actingAs($this->buyer($p, 'pending'))->get('/juguete/'.$p->slug.'/resena')->assertForbidden();
        $this->actingAs($this->buyer($p, 'cancelled'))->get('/juguete/'.$p->slug.'/resena')->assertForbidden();
        $this->actingAs($this->buyer($p, 'shipped'))->get('/juguete/'.$p->slug.'/resena')->assertOk()->assertSee('Tu calificación');
        $this->assertSame(0, Review::count());
    }

    public function test_buyer_review_waits_for_approval_and_only_once(): void
    {
        $p = $this->product();
        $buyer = $this->buyer($p);
        $this->actingAs($buyer)->get('/mi-cuenta')->assertOk()->assertSee('Dejar mi reseña');
        $this->get('/juguete/'.$p->slug)->assertSee('/juguete/'.$p->slug.'/resena', false);

        $this->post('/juguete/'.$p->slug.'/resena', $this->review(['consent' => null]))->assertSessionHasErrors('consent');
        $this->post('/juguete/'.$p->slug.'/resena', $this->review(['rating' => 6]))->assertSessionHasErrors('rating');
        $this->post('/juguete/'.$p->slug.'/resena', $this->review())->assertRedirect('/juguete/'.$p->slug.'#resenas');

        $review = Review::sole();
        $this->assertSame('pending', $review->status);
        $this->assertSame('Andrea R.', $review->author_name);
        $this->get('/juguete/'.$p->slug)->assertDontSee('Llegó rapidísimo')->assertDontSee('/juguete/'.$p->slug.'/resena', false);
        $this->get('/mi-cuenta')->assertSee('Reseña en revisión');
        $this->post('/juguete/'.$p->slug.'/resena', $this->review(['comment' => 'Segunda reseña duplicada']))->assertRedirect('/juguete/'.$p->slug.'#resenas');
        $this->assertSame(1, Review::count());
    }

    public function test_admin_approves_review_and_it_appears_on_product_and_blog(): void
    {
        $p = $this->product();
        $this->actingAs($this->buyer($p))->post('/juguete/'.$p->slug.'/resena', $this->review());
        $review = Review::sole();
        $this->post('/admin/resenas/'.$review->id.'/estado', ['status' => 'approved'])->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/resenas')->assertOk()->assertSee('Llegó rapidísimo')->assertSee('Andrea R. · Los Olivos');
        $this->post('/admin/resenas/'.$review->id.'/estado', ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $review->fresh()->status);

        $this->get('/juguete/'.$p->slug)->assertSee('Llegó rapidísimo')->assertSee('Compra verificada')->assertSee('aggregateRating', false);
        $this->get('/blog?seccion=resenas')->assertSee('Llegó rapidísimo')->assertSee('Bloques ABC');

        $this->post('/admin/resenas/'.$review->id.'/estado', ['status' => 'rejected']);
        $this->get('/juguete/'.$p->slug)->assertDontSee('Llegó rapidísimo');
        $this->delete('/admin/resenas/'.$review->id)->assertRedirect();
        $this->assertSame(0, Review::count());
    }

    public function test_public_name_hides_full_name(): void
    {
        $this->assertSame('Andrea R.', Review::publicName('andrea rojas pérez'));
        $this->assertSame('Ñuflo', Review::publicName('Ñuflo'));
    }
}
