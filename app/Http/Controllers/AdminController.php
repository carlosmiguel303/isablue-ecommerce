<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\StockHold;
use App\Models\StockMovement;
use App\Services\Checkout;
use App\Services\StoreReadiness;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /** Pestañas del tablero de pedidos: clave => [título, filtro]. */
    private const ORDER_TABS = [
        'yape' => 'Esperando Yape',
        'preparar' => 'Por preparar',
        'enviados' => 'Enviados',
        'entregados' => 'Entregados',
        'cancelados' => 'Cancelados',
        'todos' => 'Todos',
    ];

    private const LOW_STOCK = 5;

    // ───────────────────────── Resumen ─────────────────────────

    public function dashboard(Request $r)
    {
        $demo = $this->includeDemo($r);
        $now = CarbonImmutable::now();
        $periods = ['today' => $now->startOfDay(), 'week' => $now->startOfWeek(), 'month' => $now->startOfMonth()];
        $kpis = [];
        foreach ($periods as $key => $from) {
            $q = Order::sold($demo)->where('paid_at', '>=', $from);
            $kpis[$key] = [
                'total' => (float) (clone $q)->sum('total'),
                'orders' => (clone $q)->count(),
                'units' => (int) DB::table('order_items')->whereIn('order_id', (clone $q)->select('id'))->sum('quantity'),
            ];
        }

        $from = $now->subDays(13)->startOfDay();
        $byDay = Order::sold($demo)->where('paid_at', '>=', $from)
            ->selectRaw('DATE(paid_at) as d, SUM(total) as total, COUNT(*) as orders')->groupBy('d')->pluck('total', 'd');
        $chart = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $from->addDays($i);
            $chart[] = ['label' => $day->isoFormat('dd D'), 'date' => $day->toDateString(), 'total' => (float) ($byDay[$day->toDateString()] ?? 0)];
        }

        $soldIds = $this->soldItems($demo, $now->subDays(30))->distinct()->pluck('order_items.product_id');

        return view('admin.dashboard', [
            'demo' => $demo,
            'kpis' => $kpis,
            'sales' => $kpis['month']['total'],
            'chart' => $chart,
            'awaiting' => Order::with('items')->where('status', 'pending')->whereIn('payment_status', ['pending', 'review'])->latest()->take(8)->get(),
            'awaitingCount' => Order::where('status', 'pending')->whereIn('payment_status', ['pending', 'review'])->count(),
            'reviewCount' => Order::where('status', 'pending')->where('payment_status', 'review')->count(),
            'toPrepare' => Order::whereIn('status', ['confirmed', 'processing'])->count(),
            'top' => $this->soldItems($demo, $periods['month'])
                ->selectRaw('order_items.product_id, MAX(order_items.name) as name, SUM(order_items.quantity) as units, SUM(order_items.total) as total')
                ->groupBy('order_items.product_id')->orderByDesc('units')->limit(5)->get(),
            'lowStock' => Product::publicCatalog()->whereNotNull('stock')->where('stock', '<=', self::LOW_STOCK)->orderBy('stock')->take(8)->get(),
            'noSales' => Product::publicCatalog()->whereNotIn('id', $soldIds)->orderBy('name')->take(8)->get(),
            'products' => Product::publicCatalog()->count(),
        ]);
    }

    // ───────────────────────── Pedidos ─────────────────────────

    public function orders(Request $r)
    {
        $tab = array_key_exists($r->query('tab'), self::ORDER_TABS) ? $r->query('tab') : 'yape';
        $q = $this->orderTab(Order::query(), $tab)->with('items');
        if ($term = trim(substr((string) $r->query('q'), 0, 100))) {
            $q->where(fn ($b) => $b->where('reference', 'like', "%$term%")->orWhere('customer_name', 'like', "%$term%")->orWhere('phone', 'like', "%$term%")->orWhere('payment_reference', 'like', "%$term%"));
        }
        $counts = [];
        foreach (array_keys(self::ORDER_TABS) as $key) {
            $counts[$key] = $this->orderTab(Order::query(), $key)->count();
        }

        return view('admin.orders', [
            'orders' => $q->when($tab === 'yape', fn ($b) => $b->orderByRaw("payment_status = 'review' desc"))->latest()->paginate(25)->withQueryString(),
            'tab' => $tab,
            'tabs' => self::ORDER_TABS,
            'counts' => $counts,
        ]);
    }

    private function orderTab($q, string $tab)
    {
        return match ($tab) {
            'yape' => $q->where('status', 'pending')->whereIn('payment_status', ['pending', 'review']),
            'preparar' => $q->whereIn('status', ['confirmed', 'processing']),
            'enviados' => $q->where('status', 'shipped'),
            'entregados' => $q->where('status', 'completed'),
            'cancelados' => $q->where('status', 'cancelled'),
            default => $q,
        };
    }

    /** Lo consulta el panel cada minuto para avisar de pedidos nuevos. */
    public function latestOrder()
    {
        $last = Order::latest('id')->first(['id', 'reference', 'total', 'customer_name']);

        return response()->json([
            'id' => $last?->id,
            'reference' => $last?->reference,
            'text' => $last ? $last->customer_name.' · S/ '.number_format($last->total, 2) : null,
            'awaiting' => Order::where('status', 'pending')->whereIn('payment_status', ['pending', 'review'])->count(),
        ]);
    }

    public function order(Order $order)
    {
        $order->load('items.product', 'events');
        $shortages = [];
        if (! $order->stock_reserved && $order->status === 'pending') {
            foreach ($order->items as $item) {
                if ($item->product && ($item->product->stock ?? 0) < (int) $item->quantity) {
                    $shortages[] = $item->name.' (quedan '.($item->product->stock ?? 0).')';
                }
            }
        }

        return view('admin.order', ['order' => $order, 'shortages' => $shortages, 'next' => Checkout::FLOW[$order->status] ?? []]);
    }

    public function orderState(Request $r, Order $order, Checkout $checkout)
    {
        $d = $r->validate(['status' => 'required|in:pending,confirmed,processing,shipped,completed,cancelled']);
        $checkout->transition($order, $d['status'], $r->user()->id);

        return back()->with('status', 'Pedido '.$order->reference.': '.__('store.status.'.$d['status']).'.');
    }

    public function recordPayment(Request $r, Order $order, Checkout $checkout)
    {
        $d = $r->validate(['note' => 'nullable|string|max:500', 'payment_reference' => 'nullable|string|max:60']);
        if (! empty($d['payment_reference'])) {
            $order->update(['payment_reference' => $d['payment_reference']]);
        }
        $reference = $order->fresh()->payment_reference;
        $note = trim(($reference ? 'Operación: '.$reference.'. ' : '').($d['note'] ?? ''));
        $checkout->confirmPayment($order, $r->user()->id, 'Yape confirmado por '.$r->user()->name, $note ?: null);

        return back()->with('status', '✓ Yape confirmado en '.$order->reference.'. El stock ya se descontó y el pedido pasó a «Por preparar».');
    }

    /** Confirma varios Yapes de una vez desde la lista de pedidos. */
    public function bulkPayment(Request $r, Checkout $checkout)
    {
        $d = $r->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer|exists:orders,id'], ['ids.required' => 'Marca al menos un pedido.']);
        $ok = 0;
        $errors = [];
        foreach (Order::whereIn('id', $d['ids'])->orderBy('id')->get() as $order) {
            try {
                $checkout->confirmPayment($order, $r->user()->id, 'Yape confirmado por '.$r->user()->name) && $ok++;
            } catch (ValidationException $e) {
                $errors[] = $order->reference.': '.collect($e->errors())->flatten()->first();
            }
        }
        $back = back()->with('status', $ok.' '.($ok === 1 ? 'pago confirmado' : 'pagos confirmados').'. El stock se descontó automáticamente.');

        return $errors ? $back->withErrors($errors) : $back;
    }

    public function voucher(Order $order)
    {
        abort_unless($order->voucher_path && Storage::disk('local')->exists($order->voucher_path), 404);

        return Storage::disk('local')->response($order->voucher_path, null, ['Cache-Control' => 'private, no-store']);
    }

    // ───────────────────────── Inventario ─────────────────────────

    public function inventory(Request $r)
    {
        $demo = $this->includeDemo($r);
        $now = CarbonImmutable::now();
        $filter = $r->query('ver', 'publicados');
        $q = Product::with('images')->orderBy('name');
        if ($filter !== 'todos') {
            $q->publicCatalog();
        }
        if ($term = trim(substr((string) $r->query('q'), 0, 100))) {
            $q->where(fn ($b) => $b->where('name', 'like', "%$term%")->orWhere('sku', 'like', "%$term%"));
        }
        $sales = $this->soldItems($demo)
            ->selectRaw('order_items.product_id,
                SUM(CASE WHEN orders.paid_at >= ? THEN order_items.quantity ELSE 0 END) as today,
                SUM(CASE WHEN orders.paid_at >= ? THEN order_items.quantity ELSE 0 END) as week,
                SUM(CASE WHEN orders.paid_at >= ? THEN order_items.quantity ELSE 0 END) as month,
                SUM(order_items.quantity) as total, MAX(orders.paid_at) as last_sale', [$now->startOfDay(), $now->startOfWeek(), $now->startOfMonth()])
            ->groupBy('order_items.product_id')->get()->keyBy('product_id');
        if ($filter === 'bajo') {
            $q->whereNotNull('stock')->where('stock', '<=', self::LOW_STOCK);
        } elseif ($filter === 'sin-ventas') {
            $q->whereNotIn('id', $sales->filter(fn ($s) => $s->month > 0)->keys());
        }
        $pending = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'pending')->where('orders.stock_reserved', false)->whereIn('orders.payment_status', ['pending', 'review'])
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as qty')->groupBy('order_items.product_id')->pluck('qty', 'product_id');

        return view('admin.inventory', [
            'products' => $q->paginate(30)->withQueryString(),
            'sales' => $sales,
            'pending' => $pending,
            'filter' => $filter,
            'demo' => $demo,
            'low' => self::LOW_STOCK,
            'movements' => DB::table('stock_movements')->join('products', 'products.id', '=', 'stock_movements.product_id')->leftJoin('users', 'users.id', '=', 'stock_movements.user_id')->select('stock_movements.*', 'products.name', 'users.name as user_name')->orderByDesc('stock_movements.id')->limit(30)->get(),
        ]);
    }

    public function stock(Request $r, Product $product)
    {
        $d = $r->validate(['stock' => 'required|integer|min:0|max:1000000', 'reason' => 'required|string|min:3|max:255']);
        DB::transaction(function () use ($product, $d, $r) {
            $p = Product::lockForUpdate()->findOrFail($product->id);
            $before = $p->stock;
            $after = (int) $d['stock'];
            $held = (int) StockHold::active()->where('product_id', $p->id)->sum('quantity');
            if ($after < $held) {
                throw ValidationException::withMessages(['stock' => 'Hay '.$held.' unidades reservadas. Revisa esos pedidos antes de reducir el conteo.']);
            }
            StockMovement::create(['product_id' => $p->id, 'user_id' => $r->user()->id, 'before' => $before, 'change' => $after - ($before ?? 0), 'after' => $after, 'reason' => $d['reason']]);
            $p->update(['stock' => $after]);
        });

        return back()->with('status', 'Stock de «'.$product->name.'» actualizado a '.$d['stock'].' unidades.');
    }

    // ───────────────────────── Reportes ─────────────────────────

    public function reports(Request $r)
    {
        $demo = $this->includeDemo($r);
        $now = CarbonImmutable::now();
        $preset = $r->query('periodo', 'mes');
        [$from, $to] = match ($preset) {
            'hoy' => [$now->startOfDay(), $now->endOfDay()],
            'ayer' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'semana' => [$now->startOfWeek(), $now->endOfDay()],
            'mes-pasado' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'rango' => [
                CarbonImmutable::parse($r->validate(['desde' => 'required|date'])['desde'])->startOfDay(),
                CarbonImmutable::parse($r->validate(['hasta' => 'required|date|after_or_equal:desde'])['hasta'])->endOfDay(),
            ],
            default => [$now->startOfMonth(), $now->endOfDay()],
        };
        $orders = Order::sold($demo)->whereBetween('paid_at', [$from, $to]);
        $total = (float) (clone $orders)->sum('total');
        $count = (clone $orders)->count();
        $items = $this->soldItems($demo)->whereBetween('orders.paid_at', [$from, $to]);
        $byProduct = (clone $items)->selectRaw('order_items.product_id, MAX(order_items.name) as name, MAX(order_items.sku) as sku, SUM(order_items.quantity) as units, SUM(order_items.total) as total, COUNT(DISTINCT orders.id) as orders')
            ->groupBy('order_items.product_id')->orderByDesc('units')->get();
        $byDay = (clone $orders)->selectRaw('DATE(paid_at) as d, COUNT(*) as orders, SUM(total) as total, SUM(shipping_total) as shipping')->groupBy('d')->orderBy('d')->get();

        if ($r->query('exportar') === 'csv') {
            return response()->streamDownload(function () use ($orders) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Pedido', 'Fecha de pago', 'Cliente', 'Celular', 'Productos', 'Subtotal', 'Envío', 'Total', 'Operación Yape', 'Estado'], ';');
                foreach ((clone $orders)->with('items')->orderBy('paid_at')->cursor() as $o) {
                    fputcsv($out, [$o->reference, $o->paid_at?->format('d/m/Y H:i'), $o->customer_name, $o->phone, $o->items->map(fn ($i) => (float) $i->quantity.' x '.$i->name)->join(' | '), $o->subtotal, $o->shipping_total, $o->total, $o->payment_reference, __('store.status.'.$o->status)], ';');
                }
                fclose($out);
            }, 'ventas-isablue-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('admin.reports', [
            'demo' => $demo, 'preset' => $preset, 'from' => $from, 'to' => $to,
            'total' => $total, 'count' => $count,
            'units' => (int) $byProduct->sum('units'),
            'average' => $count ? $total / $count : 0,
            'byProduct' => $byProduct, 'byDay' => $byDay,
        ]);
    }

    /** Líneas vendidas (pedidos con Yape confirmado). */
    private function soldItems(bool $demo, $since = null)
    {
        $q = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')->where('orders.currency', 'PEN')->where('orders.status', '!=', 'cancelled')
            ->whereNotNull('order_items.product_id');
        if (! $demo) {
            $q->where('orders.is_demo', false);
        }

        return $since ? $q->where('orders.paid_at', '>=', $since) : $q;
    }

    /** En modo de prueba se muestran también las ventas de prueba; si no, solo con ?prueba=1. */
    private function includeDemo(Request $r): bool
    {
        if ($r->has('prueba')) {
            return $r->boolean('prueba');
        }

        return Setting::valueOf('demo_mode', '0') === '1';
    }

    // ───────────────────────── Productos ─────────────────────────

    public function products(Request $r)
    {
        $q = Product::with('images', 'categories');
        if ($term = $r->query('q')) {
            $q->where(fn ($b) => $b->where('name', 'like', '%'.substr($term, 0, 100).'%')->orWhere('sku', 'like', '%'.substr($term, 0, 100).'%'));
        }
        if ($r->query('ver') === 'publicados') {
            $q->publicCatalog();
        }

        return view('admin.products', ['products' => $q->orderByDesc('id')->paginate(20)->withQueryString()]);
    }

    public function productForm(?Product $product = null)
    {
        return view('admin.product-form', ['product' => $product ?? new Product, 'categories' => Category::orderBy('sort_order')->get()]);
    }

    public function saveProduct(Request $r, ?Product $product = null)
    {
        $product ??= new Product;
        $d = $r->validate(['name' => 'required|string|max:200', 'sku' => 'required|string|max:100', 'description' => 'required|string|max:10000', 'price' => 'required|numeric|min:0.01|max:99999999', 'compare_price' => 'nullable|numeric|gt:price|max:99999999', 'age_from' => ['nullable', Rule::in(array_map('strval', array_keys(Product::AGE_STAGES)))], 'age_to' => ['nullable', Rule::in(array_map('strval', array_keys(Product::AGE_STAGES)))], 'age_source' => 'nullable|string|max:255', 'specifications' => 'nullable|string|max:5000', 'material' => 'nullable|string|max:255', 'brand' => 'nullable|string|max:255', 'safety' => 'nullable|string|max:2000', 'categories' => 'required|array|min:1', 'categories.*' => 'integer|exists:categories,id', 'photos' => 'nullable|array|max:10', 'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120', 'image_order' => 'nullable|array', 'image_order.*' => 'integer|min:0|max:1000', 'delete_images' => 'nullable|array', 'delete_images.*' => 'integer']);
        DB::transaction(function () use ($r, $d, $product) {
            $fields = collect($d)->except(['categories', 'photos', 'image_order', 'delete_images'])->all();
            $from = Product::AGE_STAGES[$d['age_from'] ?? ''] ?? null;
            $to = Product::AGE_STAGES[$d['age_to'] ?? ''] ?? $from;
            unset($fields['age_from'], $fields['age_to']);
            $fields['age_min_months'] = $from ? min($from[1], $to[1]) : null;
            $fields['age_max_months'] = $from ? max($from[2], $to[2]) : null;
            foreach (['active', 'featured', 'catalog_visible', 'demo_data'] as $b) {
                $fields[$b] = $r->boolean($b);
            }
            if (! $product->exists) {
                $fields['slug'] = Str::slug($d['name']).'-'.Str::lower(Str::random(6));
            }
            $product->fill($fields)->save();
            $product->categories()->sync($d['categories']);
            if (! empty($d['delete_images'])) {
                $product->images()->whereIn('id', $d['delete_images'])->delete();
            }
            foreach ($r->file('photos', []) as $file) {
                $product->images()->create(['path' => $this->upload($file), 'sort_order' => 100]);
            }
            foreach ($d['image_order'] ?? [] as $id => $position) {
                $product->images()->where('id', $id)->update(['sort_order' => $position]);
            }
        });

        return redirect('/admin/productos/'.$product->id.'/editar')->with('status', 'Producto guardado. La tienda ya muestra los cambios.');
    }

    // ───────────────────────── Categorías y banners ─────────────────────────

    public function categories()
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->orderBy('sort_order')->get()]);
    }

    public function categoryForm(?Category $category = null)
    {
        return view('admin.category-form', ['category' => $category ?? new Category]);
    }

    public function saveCategory(Request $r, ?Category $category = null)
    {
        $category ??= new Category;
        $d = $r->validate(['name' => 'required|string|max:150', 'slug' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:180', Rule::unique('categories')->ignore($category->id)], 'description' => 'nullable|string|max:2000', 'sort_order' => 'required|integer|min:0|max:1000', 'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120']);
        unset($d['photo']);
        $d['active'] = $r->boolean('active');
        if ($r->hasFile('photo')) {
            $d['image'] = $this->upload($r->file('photo'));
        }
        $category->fill($d)->save();

        return redirect('/admin/categorias')->with('status', 'Categoría guardada.');
    }

    public function posts()
    {
        return view('admin.posts', ['posts' => Post::latest()->paginate(20)]);
    }

    public function postForm(?Post $post = null)
    {
        return view('admin.post-form', ['post' => $post ?? new Post]);
    }

    public function savePost(Request $r, ?Post $post = null)
    {
        $post ??= new Post;
        $d = $r->validate(['title' => 'required|string|max:200', 'slug' => ['nullable', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:220', Rule::unique('posts')->ignore($post->id)], 'excerpt' => 'nullable|string|max:300', 'body' => 'required|string|max:60000', 'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120']);
        unset($d['photo']);
        $d['slug'] = ($d['slug'] ?? null) ?: Str::slug($d['title']).($post->exists ? '' : '-'.Str::lower(Str::random(4)));
        $d['published'] = $r->boolean('published');
        if ($d['published'] && ! $post->published_at) {
            $d['published_at'] = now();
        }
        if ($r->hasFile('photo')) {
            $d['image'] = $this->upload($r->file('photo'));
        }
        $post->fill($d)->save();

        return redirect('/admin/blog')->with('status', $d['published'] ? 'Artículo publicado en el blog.' : 'Artículo guardado como borrador.');
    }

    public function deletePost(Post $post)
    {
        $post->delete();

        return redirect('/admin/blog')->with('status', 'Artículo eliminado.');
    }

    public function banners()
    {
        return view('admin.banners', ['banners' => Banner::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function bannerForm(?Banner $banner = null)
    {
        return view('admin.banner-form', ['banner' => $banner ?? new Banner]);
    }

    public function saveBanner(Request $r, ?Banner $banner = null)
    {
        $banner ??= new Banner;
        $d = $r->validate(['title' => 'required|string|max:180', 'subtitle' => 'nullable|string|max:500', 'eyebrow' => 'nullable|string|max:100', 'button' => 'required|string|max:60', 'link' => ['required', 'string', 'max:255', 'regex:~^/(?!/)[a-zA-Z0-9/_?=&%.-]*$~'], 'theme' => 'required|in:image,main,baby,montessori,sunny,mint', 'sort_order' => 'required|integer|min:0|max:1000', 'photo' => [Rule::requiredIf($r->input('theme') === 'image' && ! $banner->image), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        unset($d['photo']);
        $d['active'] = $r->boolean('active');
        if ($r->hasFile('photo')) {
            $d['image'] = $this->upload($r->file('photo'));
        }
        $banner->fill($d)->save();

        return redirect('/admin/banners')->with('status', 'Banner guardado.');
    }

    public function uploadBanners(Request $r)
    {
        $d = $r->validate([
            'title' => 'required|string|max:160',
            'link' => ['required', 'string', 'max:255', 'regex:~^/(?!/)[a-zA-Z0-9/_?=&%.-]*$~'],
            'photos' => 'required|array|min:1|max:5',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $uploaded = [];
        try {
            DB::transaction(function () use ($r, $d, &$uploaded) {
                $order = min(995, (int) Banner::max('sort_order') + 1);
                foreach ($r->file('photos') as $index => $photo) {
                    $uploaded[] = $path = $this->upload($photo);
                    Banner::create([
                        'title' => $d['title'].' '.($index + 1), 'image' => $path,
                        'link' => $d['link'], 'button' => 'Ver promoción', 'theme' => 'image',
                        'sort_order' => $order + $index, 'active' => true,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($uploaded as $path) {
                @unlink(public_path(ltrim($path, '/')));
            }
            throw $e;
        }

        return redirect('/admin/banners')->with('status', 'Imágenes añadidas al carrusel. Puedes editar el título, enlace y orden de cada una.');
    }

    public function deleteBanner(Banner $banner)
    {
        // Keep the uploaded file: another banner may still reference it.
        $banner->delete();

        return redirect('/admin/banners')->with('status', 'Banner eliminado del carrusel.');
    }

    // ───────────────────────── Configuración ─────────────────────────

    public function settings()
    {
        return view('admin.settings', [
            'settings' => Setting::pluck('value', 'key'),
            'shipping' => ShippingMethod::all(),
            'products' => Product::publicCatalog()->orderBy('name')->get(['id', 'name', 'price']),
            'launchIssues' => StoreReadiness::launchIssues(),
            'mailReady' => ! in_array(config('mail.default'), ['log', 'array']),
        ]);
    }

    public function saveSettings(Request $r)
    {
        $d = $r->validate([
            'contact_email' => 'nullable|email|max:255',
            'whatsapp' => ['nullable', 'regex:/^[0-9]{8,15}$/'],
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:500',
            'hours' => 'nullable|string|max:200',
            'delivery_info' => 'nullable|string|max:1000',
            'privacy' => 'nullable|string|max:10000',
            'returns' => 'nullable|string|max:10000',
            'instagram' => 'nullable|url:https|max:255',
            'facebook' => 'nullable|url:https|max:255',
            'tiktok' => 'nullable|url:https|max:255',
            // Yape
            'yape_number' => ['nullable', 'regex:/^[0-9 ]{9,12}$/'],
            'yape_holder' => 'nullable|string|max:120',
            'yape_qr' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            // Promociones
            'promo_text' => 'nullable|string|max:160',
            'promo_subtext' => 'nullable|string|max:160',
            'promo_ends_at' => 'nullable|date',
            'promo_cta_text' => 'nullable|string|max:40',
            'promo_cta_link' => ['nullable', 'string', 'max:255', 'regex:~^/(?!/)[a-zA-Z0-9/_?=&%.-]*$~'],
            'shipping_bar_text' => 'nullable|string|max:200',
            'free_shipping_from' => 'nullable|numeric|min:0|max:100000',
            'popup_title' => 'nullable|string|max:120',
            'popup_headline' => 'nullable|string|max:120',
            'popup_product_id' => 'nullable|integer|exists:products,id',
            'popup_delay' => 'nullable|integer|min:0|max:120',
        ]);
        if (! $r->boolean('demo_mode') && (Product::where('demo_data', true)->where('active', true)->where('catalog_visible', true)->exists() || ShippingMethod::where('demo_data', true)->where('active', true)->exists())) {
            throw ValidationException::withMessages(['demo_mode' => 'Reemplaza o desactiva primero todos los productos y envíos de prueba.']);
        }
        unset($d['yape_qr']);
        if ($r->hasFile('yape_qr')) {
            $d['yape_qr'] = $this->upload($r->file('yape_qr'));
        }
        $d['demo_mode'] = $r->boolean('demo_mode') ? '1' : '0';
        foreach (['promo_enabled', 'popup_enabled'] as $flag) {
            if ($r->has($flag.'_present')) {
                $d[$flag] = $r->boolean($flag) ? '1' : '0';
            }
        }
        foreach ($d as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => $v]);
        }

        return back()->with('status', 'Configuración guardada. Los cambios ya se ven en la tienda.');
    }

    public function shipping(Request $r)
    {
        $d = $r->validate(['id' => 'nullable|integer|exists:shipping_methods,id', 'name' => 'required|string|max:150', 'description' => 'nullable|string|max:500', 'price' => 'required|numeric|min:0|max:99999']);
        $id = $d['id'] ?? null;
        unset($d['id']);
        $d['active'] = $r->boolean('active');
        $d['demo_data'] = $r->boolean('demo_data');
        if ($id) {
            ShippingMethod::findOrFail($id)->update($d);
        } else {
            ShippingMethod::create($d);
        }

        return back()->with('status', 'Opción de entrega guardada.');
    }

    private function upload($file): string
    {
        $dir = public_path('uploads');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = Str::uuid().'.'.$file->extension();
        $file->move($dir, $name);

        return '/uploads/'.$name;
    }
}
