<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use Illuminate\Validation\ValidationException;

class StoreReadiness
{
    public static function paymentReady(): bool
    {
        return preg_match('/^9[0-9]{8}$/', preg_replace('/\s/', '', Setting::valueOf('yape_number'))) === 1
            && trim(Setting::valueOf('yape_holder')) !== '';
    }

    public static function launchIssues(): array
    {
        $issues = [];
        if (! self::paymentReady()) {
            $issues[] = 'Completa el número de Yape (9 dígitos) y su titular en Datos de pago.';
        }
        if (! ShippingMethod::where('active', true)->where('demo_data', false)->exists()) {
            $issues[] = 'Configura al menos una opción de entrega real, con cobertura y tarifa verificadas.';
        }
        if (ShippingMethod::where('active', true)->where('demo_data', true)->exists()) {
            $issues[] = 'Revisa o desactiva las opciones de entrega marcadas como prueba.';
        }
        if (Product::publicCatalog()->where('demo_data', true)->exists()) {
            $issues[] = 'Verifica precios, edades y existencias de los productos; luego desmarca «Datos de prueba» en sus fichas.';
        }
        if (! Product::publicCatalog()->where('demo_data', false)->where('stock', '>', 0)->exists()) {
            $issues[] = 'Necesitas al menos un producto verificado con existencias para recibir pedidos.';
        }

        return $issues;
    }

    public static function assertCanOrder(): void
    {
        if (Setting::valueOf('demo_mode', '0') !== '1' && ! self::paymentReady()) {
            throw ValidationException::withMessages(['payment' => 'Todavía no está disponible el pago. Contacta con la tienda antes de realizar un abono.']);
        }
    }
}
