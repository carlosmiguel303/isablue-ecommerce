<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'featured' => 'boolean', 'catalog_visible' => 'boolean', 'price' => 'decimal:2', 'compare_price' => 'decimal:2', 'legacy_data' => 'array'];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublicCatalog($q)
    {
        return $q->where('active', true)->where('catalog_visible', true);
    }

    public function getImageAttribute()
    {
        return $this->images->first()?->path ?: '/assets/images/oso-logo.png';
    }

    public function getSellableStockAttribute(): int
    {
        return max(0, (int) $this->stock - (int) StockHold::active()->where('product_id', $this->id)->sum('quantity'));
    }

    public function scopeWithAvailability($query, bool $available = true)
    {
        $holds = StockHold::active()->whereColumn('stock_holds.product_id', 'products.id')->selectRaw('COALESCE(SUM(quantity),0)');

        return $query->whereRaw('COALESCE(products.stock,0) - ('.$holds->toSql().') '.($available ? '>' : '<=').' 0', $holds->getBindings());
    }

    public function getAvailableAttribute(): bool
    {
        return $this->active && $this->catalog_visible && $this->stock !== null && $this->sellable_stock > 0;
    }

    public function getAgeLabelAttribute(): string
    {
        if ($this->age_min_months === null) {
            return 'Edad por confirmar';
        }

        return $this->age_min_months.'–'.$this->age_max_months.' meses';
    }

    public function getOnSaleAttribute(): bool
    {
        return $this->compare_price !== null && $this->compare_price > $this->price;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        return $this->on_sale ? (int) round(100 - ($this->price / $this->compare_price) * 100) : null;
    }

    /** Pocas unidades: se muestra como urgencia en la tienda. */
    public function getLowStockAttribute(): bool
    {
        return $this->available && $this->sellable_stock <= 5;
    }

    public function getIsNewAttribute(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subDays(30));
    }
}
