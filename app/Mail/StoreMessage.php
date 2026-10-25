<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class StoreMessage extends Mailable
{
    public function __construct(public string $kind, public array $data) {}

    public function build()
    {
        return $this->subject($this->data['subject'])->view('emails.message', ['data' => $this->data, 'kind' => $this->kind]);
    }
}
