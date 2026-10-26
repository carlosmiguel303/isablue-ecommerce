<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    /** Clasificación por edad de la tienda: clave => [etiqueta, mes mínimo, mes máximo]. */
    const AGE_STAGES = [
        '0-3m' => ['0 a 3 meses', 0, 2],
        '3-11m' => ['3 a 11 meses', 3, 11],
        '1' => ['1 año', 12, 23],
        '2' => ['2 años', 24, 35],
        '3' => ['3 años', 36, 47],
        '4' => ['4 años', 48, 59],
        '5' => ['5 años a más', 60, 216],
    ];

    /** Etapa que contiene un mes de edad, para preseleccionar el formulario. */
    public static function stageFor(?int $months): ?string
    {
        if ($months === null) {
            return null;
        }
        foreach (self::AGE_STAGES as $key => [, $min, $max]) {
            if ($months >= $min && $months <= $max) {
                return $key;
            }
        }

        return '5';
    }

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
        [$min, $max] = [(int) $this->age_min_months, (int) $this->age_max_months];
        foreach (self::AGE_STAGES as [$label, $from, $to]) {
            if ($min === $from && $max === $to) {
                return $label;
            }
        }
        $years = fn (int $m) => intdiv($m, 12).(intdiv($m, 12) === 1 ? ' año' : ' años');
        if ($max >= 216) {
            return 'Desde '.($min < 12 ? $min.' meses' : $years($min));
        }
        if ($max < 12) {
            return $min.' a '.$max.' meses';
        }
        if ($min === 0) {
            return '0 a '.$years($max);
        }
        if ($min < 12) {
            return $min.' meses a '.$years($max);
        }

        return intdiv($min, 12) === intdiv($max, 12) ? $years($min) : intdiv($min, 12).' a '.$years($max);
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
