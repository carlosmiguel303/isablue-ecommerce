<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /** Enlaces antiguos por rango de años, se mantienen para no romper marcadores. */
    const LEGACY_AGES = ['0-2' => [0, 35], '3-4' => [36, 59]];

    public static function ageRange(string $key): array
    {
        return self::LEGACY_AGES[$key] ?? array_slice(Product::AGE_STAGES[$key], 1);
    }

    public function home()
    {
        $catalog = fn () => Product::publicCatalog()->with('images', 'categories');
        $offers = $catalog()->whereColumn('compare_price', '>', 'price')->orderByRaw('stock > 0 desc')->take(8)->get();
        $featured = $catalog()->where('featured', true)->orderByRaw('stock > 0 desc')->take(8)->get();

        return view('home', [
            'banners' => Banner::where('active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'categories' => Category::where('active', true)->orderBy('sort_order')->get(),
            'featured' => $featured->isNotEmpty() ? $featured : $catalog()->orderByRaw('stock > 0 desc')->take(8)->get(),
            'newProducts' => $catalog()->latest()->take(4)->get(),
            'offers' => $offers,
            'maxDiscount' => $offers->max('discount_percent'),
        ]);
    }

    public function catalog(Request $r, ?string $slug = null)
    {
        if ($slug) {
            $category = Category::where('slug', $slug)->where('active', true)->firstOrFail();
            $r->merge(['category' => [$category->id]]);
        } $d = $r->validate(['q' => 'nullable|string|max:100', 'age' => 'nullable|in:'.implode(',', array_merge(array_keys(Product::AGE_STAGES), array_keys(self::LEGACY_AGES))), 'category' => 'nullable|array', 'category.*' => 'integer|exists:categories,id', 'min' => 'nullable|numeric|min:0|max:99999999', 'max' => 'nullable|numeric|min:0|max:99999999', 'availability' => 'nullable|in:available,out', 'sale' => 'nullable|in:1', 'sort' => 'nullable|in:newest,price_asc,price_desc,name']);
        $q = Product::publicCatalog()->with('images', 'categories');
        if ($term = trim($d['q'] ?? '')) {
            $q->where(fn ($b) => $b->where('name', 'like', '%'.$term.'%')->orWhere('description', 'like', '%'.$term.'%')->orWhere('sku', 'like', '%'.$term.'%'));
        }if (! empty($d['age'])) {
            [$min,$max] = self::ageRange($d['age']);
            $q->whereNotNull('age_min_months')->whereNotNull('age_max_months')->where('age_min_months', '<=', $max)->where('age_max_months', '>=', $min);
        }if (! empty($d['category'])) {
            $q->whereHas('categories', fn ($b) => $b->where('active', true)->whereIn('categories.id', $d['category']));
        }if (isset($d['min'])) {
            $q->where('price', '>=', $d['min']);
        }if (isset($d['max'])) {
            $q->where('price', '<=', $d['max']);
        }if (($d['availability'] ?? '') === 'available') {
            $q->withAvailability();
        }if (($d['availability'] ?? '') === 'out') {
            $q->withAvailability(false);
        }if (! empty($d['sale'])) {
            $q->whereColumn('compare_price', '>', 'price');
        }$sort = $d['sort'] ?? 'newest';
        match ($sort) {
            'price_asc' => $q->orderBy('price'),'price_desc' => $q->orderByDesc('price'),'name' => $q->orderBy('name'),default => $q->latest()
        };

        return view('catalog', ['products' => $q->orderBy('id')->paginate(12)->withQueryString(), 'categories' => Category::where('active', true)->orderBy('sort_order')->get(), 'filters' => $d]);
    }

    public function product(string $slug)
    {
        $product = Product::publicCatalog()->with('images', 'categories')->where('slug', $slug)->firstOrFail();
        $ids = $product->categories->pluck('id');
        $related = Product::publicCatalog()->with('images', 'categories')->where('id', '!=', $product->id)->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids))->take(4)->get();

        return view('product', compact('product', 'related'));
    }

    public function suggestions(Request $r)
    {
        $d = $r->validate(['q' => 'required|string|min:2|max:100']);

        return Product::publicCatalog()->where('name', 'like', '%'.$d['q'].'%')->limit(6)->get(['name', 'slug']);
    }
}
