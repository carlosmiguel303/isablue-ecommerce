<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    /** Debe usar el importe del pedido en el servidor; nunca datos de tarjeta. */
    public function createCheckout(Order $order): string;

    /** Debe validar la firma y consultar el pago al proveedor antes de devolver un evento. */
    public function verifiedEvent(string $payload, array $headers): array;
}
