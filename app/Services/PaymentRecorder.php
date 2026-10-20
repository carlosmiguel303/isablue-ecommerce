<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentRecorder
{
    public function __construct(private Checkout $checkout) {}

    /** Solo se invoca con un evento ya validado por un adaptador PaymentGateway. No hay ruta pública hasta elegir proveedor. */
    public function recordVerified(string $provider, string $eventId, int $orderId, int $amountCents, string $currency, array $payload): bool
    {
        return DB::transaction(function () use ($provider, $eventId, $orderId, $amountCents, $currency, $payload) {
            $o = Order::lockForUpdate()->findOrFail($orderId);
            if (DB::table('payment_events')->where('provider', $provider)->where('external_id', $eventId)->exists()) {
                return false;
            }
            if ($currency !== 'PEN' || Cart::cents($o->total) !== $amountCents || $o->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'Importe, moneda o estado de pedido incompatibles.']);
            }
            DB::table('payment_events')->insert(['provider' => $provider, 'external_id' => $eventId, 'order_id' => $o->id, 'payload' => json_encode($payload), 'created_at' => now(), 'updated_at' => now()]);
            $this->checkout->confirmPayment($o, null, 'Pago verificado por '.$provider);

            return true;
        });
    }
}
