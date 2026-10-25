<?php

namespace App\Http\Middleware;

use App\Services\DatabaseBackup;
use App\Services\MailOutbox;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunStoreMaintenance
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response): void
    {
        if (app()->runningInConsole()) {
            return;
        }
        if (! Cache::add('maintenance-tick', true, 60)) {
            return;
        }
        $lock = Cache::lock('store-maintenance', 120);
        if (! $lock->get()) {
            return;
        }
        try {
            MailOutbox::process(2);
            if (DatabaseBackup::due()) {
                DatabaseBackup::create();
            }
        } catch (\Throwable $e) {
            Log::warning('Mantenimiento pendiente; revisar integraciones y respaldo.');
        } finally {
            $lock->release();
        }
    }
}
