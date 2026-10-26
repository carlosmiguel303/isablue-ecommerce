<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderConfirmation extends Mailable
{
    public function __construct(public Order $order) {}

    public function build()
    {
        return $this->subject('Isablue · Pedido '.$this->order->reference)->view('emails.order');
    }
}
