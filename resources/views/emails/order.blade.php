<div style="font-family:Arial,sans-serif;color:#2A2346;max-width:520px">
<h1 style="color:#E9407A">¡Gracias por tu pedido en Isablue!</h1>
<p>Pedido <strong>{{ $order->reference }}</strong> · Total: <strong>S/ {{ number_format($order->total,2) }}</strong>.</p>
<p>Forma de pago: <strong>{{ $order->payment_label }}</strong>.</p>
@if($order->pays_with_yape)<p>Para completar tu compra, yapea el total y envíanos tu comprobante desde la página de tu pedido. Separamos tus juguetes cuando confirmamos el pago.</p>@else<p>Te contactaremos para coordinar el pago y la entrega. Reservamos tus juguetes por 24 horas.</p>@endif
<p><a href="{{ url('/pedido/'.$order->public_token) }}" style="background:#E9407A;color:#fff;padding:12px 20px;border-radius:999px;text-decoration:none;font-weight:bold">{{ $order->pays_with_yape ? 'Ver mi pedido y pagar con Yape' : 'Ver mi pedido' }}</a></p>
</div>
