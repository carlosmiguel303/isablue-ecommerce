<?php

namespace App\Services;

use App\Mail\StoreMessage;
use App\Models\OutboundMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailOutbox
{
    public static function enqueue(string $key, string $kind, string $recipient, array $payload, $expiry = null): OutboundMail
    {
        return OutboundMail::firstOrCreate(['event_key' => $key], ['kind' => $kind, 'recipient' => $recipient, 'payload' => $payload, 'available_at' => now(), 'expires_at' => $expiry ?? now()->addDays(7)]);
    }

    public static function login(User $user): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }
        self::enqueue('login:'.Str::uuid(), 'login', $user->email, ['subject' => 'Nuevo inicio de sesión en IsaBlue', 'name' => $user->name, 'message' => 'Se inició sesión correctamente en tu cuenta.', 'details' => 'Fecha y hora: '.now()->format('d/m/Y H:i').' (Lima).', 'url' => url('/mi-cuenta'), 'action' => 'Revisar mi cuenta'], now()->addDay());
    }

    public static function process(int $limit = 3): int
    {
        if (! Integrations::mailReady()) {
            return 0;
        }
        $sent = 0;
        for ($i = 0; $i < $limit; $i++) {
            $mail = DB::transaction(function () {
                $m = OutboundMail::whereNull('sent_at')->where('attempts', '<', 8)->where('available_at', '<=', now())->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->where(fn ($q) => $q->whereNull('locked_at')->orWhere('locked_at', '<', now()->subMinutes(5)))->orderBy('id')->lockForUpdate()->first();
                if ($m) {
                    $m->update(['locked_at' => now(), 'attempts' => $m->attempts + 1]);
                }

                return $m;
            });
            if (! $mail) {
                break;
            }
            try {
                Mail::to($mail->recipient)->send(new StoreMessage($mail->kind, $mail->payload));
                $mail->update(['sent_at' => now(), 'locked_at' => null, 'last_error' => null]);
                $sent++;
            } catch (\Throwable $e) {
                $mail->update(['locked_at' => null, 'available_at' => now()->addMinutes(min(60, 2 ** $mail->attempts)), 'last_error' => 'No se pudo entregar. Revisa el servidor SMTP y el remitente.']);
            }
        }

        return $sent;
    }
}
