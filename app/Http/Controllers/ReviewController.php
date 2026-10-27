<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function create(Request $r, string $slug)
    {
        [$product, $order] = $this->eligible($r, $slug);

        return view('review-form', ['product' => $product, 'order' => $order]);
    }

    public function store(Request $r, string $slug)
    {
        [$product, $order] = $this->eligible($r, $slug);
        $d = $r->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
            'district' => 'nullable|string|max:60',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'consent' => 'accepted',
        ], [
            'rating.required' => 'Elige de 1 a 5 estrellas.',
            'comment.min' => 'Cuéntanos un poco más (al menos 10 caracteres).',
            'consent.accepted' => 'Necesitamos tu autorización para publicar la reseña.',
        ]);
        $photo = null;
        if ($r->hasFile('photo')) {
            $dir = public_path('uploads');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = Str::uuid().'.'.$r->file('photo')->extension();
            $r->file('photo')->move($dir, $name);
            $photo = '/uploads/'.$name;
        }
        Review::create([
            'product_id' => $product->id, 'user_id' => $r->user()->id, 'order_id' => $order->id,
            'rating' => $d['rating'], 'comment' => trim($d['comment']), 'district' => $d['district'] ?? null,
            'photo' => $photo, 'author_name' => Review::publicName($r->user()->name), 'status' => 'pending',
        ]);

        return redirect('/juguete/'.$product->slug.'#resenas')->with('status', '¡Gracias por tu reseña! La publicaremos apenas la revisemos.');
    }

    /** Solo compradores con pedido enviado o entregado, y una reseña por juguete. */
    private function eligible(Request $r, string $slug): array
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $order = Review::purchaseOrder($r->user(), $product);
        abort_unless($order, 403, 'Solo pueden dejar reseña quienes compraron este juguete.');
        if (Review::where('user_id', $r->user()->id)->where('product_id', $product->id)->exists()) {
            abort(redirect('/juguete/'.$product->slug.'#resenas')->with('status', 'Ya dejaste tu reseña para este juguete. ¡Gracias!'));
        }

        return [$product, $order];
    }
}
