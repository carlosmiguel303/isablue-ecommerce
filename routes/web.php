<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/catalogo', [CatalogController::class, 'catalog'])->name('catalog');
Route::get('/categoria/{slug}', [CatalogController::class, 'catalog']);
Route::get('/juguete/{slug}', [CatalogController::class, 'product'])->name('product');
Route::get('/buscar/sugerencias', [CatalogController::class, 'suggestions'])->middleware('throttle:60,1');
Route::get('/ingresar', [AuthController::class, 'loginForm'])->name('login');
Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::get('/registro', [AuthController::class, 'registerForm']);
Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/salir', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/mi-cuenta', [AuthController::class, 'account'])->middleware('auth');
Route::get('/carrito', [CartController::class, 'index']);
Route::get('/carrito/mini', [CartController::class, 'mini']);
Route::post('/carrito', [CartController::class, 'add'])->middleware('throttle:60,1');
Route::patch('/carrito/{id}', [CartController::class, 'update']);
Route::get('/checkout', [CheckoutController::class, 'index']);
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1');
Route::get('/pedido/{token}', [CheckoutController::class, 'show'])->whereUuid('token');
Route::post('/pedido/{token}/comprobante', [CheckoutController::class, 'voucher'])->whereUuid('token')->middleware('throttle:10,1');
Route::view('/nosotros', 'pages.about');
Route::view('/contacto', 'pages.contact');
Route::view('/como-comprar', 'pages.how');
Route::get('/politicas/{type}', function (string $type) {
    abort_unless(in_array($type, ['privacy', 'returns']), 404);

    return view('policy', ['type' => $type, 'content' => Setting::valueOf($type)]);
});
Route::get('/sitemap.xml', function () {
    return response()->view('sitemap', ['products' => Product::publicCatalog()->get(), 'categories' => Category::where('active', true)->get()])->header('Content-Type', 'application/xml');
});
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard']);
    Route::get('/productos', [AdminController::class, 'products']);
    Route::get('/productos/nuevo', [AdminController::class, 'productForm']);
    Route::post('/productos', [AdminController::class, 'saveProduct']);
    Route::get('/productos/{product}/editar', [AdminController::class, 'productForm']);
    Route::put('/productos/{product}', [AdminController::class, 'saveProduct']);
    Route::post('/productos/{product}/stock', [AdminController::class, 'stock']);
    Route::get('/inventario', [AdminController::class, 'inventory']);
    Route::get('/categorias', [AdminController::class, 'categories']);
    Route::get('/categorias/nueva', [AdminController::class, 'categoryForm']);
    Route::post('/categorias', [AdminController::class, 'saveCategory']);
    Route::get('/categorias/{category}/editar', [AdminController::class, 'categoryForm']);
    Route::put('/categorias/{category}', [AdminController::class, 'saveCategory']);
    Route::get('/banners', [AdminController::class, 'banners']);
    Route::get('/banners/nuevo', [AdminController::class, 'bannerForm']);
    Route::post('/banners', [AdminController::class, 'saveBanner']);
    Route::post('/banners/imagenes', [AdminController::class, 'uploadBanners']);
    Route::get('/banners/{banner}/editar', [AdminController::class, 'bannerForm']);
    Route::put('/banners/{banner}', [AdminController::class, 'saveBanner']);
    Route::delete('/banners/{banner}', [AdminController::class, 'deleteBanner']);
    Route::get('/pedidos', [AdminController::class, 'orders']);
    Route::get('/pedidos/ultimo', [AdminController::class, 'latestOrder']);
    Route::post('/pedidos/confirmar-yape', [AdminController::class, 'bulkPayment']);
    Route::get('/pedidos/{order}', [AdminController::class, 'order']);
    Route::get('/pedidos/{order}/comprobante', [AdminController::class, 'voucher']);
    Route::post('/pedidos/{order}/estado', [AdminController::class, 'orderState']);
    Route::post('/pedidos/{order}/pago', [AdminController::class, 'recordPayment']);
    Route::get('/reportes', [AdminController::class, 'reports']);
    Route::get('/configuracion', [AdminController::class, 'settings']);
    Route::post('/configuracion', [AdminController::class, 'saveSettings']);
    Route::post('/envios', [AdminController::class, 'shipping']);
});
