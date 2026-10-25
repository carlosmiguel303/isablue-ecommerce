<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundMail extends Model
{
    protected $table = 'mail_outbox';

    protected $guarded = [];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'available_at' => 'datetime', 'locked_at' => 'datetime', 'sent_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
