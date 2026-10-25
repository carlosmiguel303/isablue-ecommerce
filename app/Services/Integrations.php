<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Schema;

class Integrations
{
    public static function data(string $key): array
    {
        return IntegrationSetting::find($key)?->value ?? [];
    }

    public static function apply(): void
    {
        if (! config('app.key') || ! Schema::hasTable('integration_settings')) {
            return;
        }
        $g = self::data('google');
        config(['services.google' => ['client_id' => $g['client_id'] ?? env('GOOGLE_CLIENT_ID'), 'client_secret' => $g['client_secret'] ?? env('GOOGLE_CLIENT_SECRET'), 'redirect' => rtrim(config('app.url'), '/').'/auth/google/callback', 'enabled' => (bool) ($g['enabled'] ?? env('GOOGLE_ENABLED', false))]]);
        $m = self::data('smtp');
        if ($m && ! ($m['enabled'] ?? false)) {
            config(['mail.default' => 'log']);
        }
        if (($m['enabled'] ?? false) && ! empty($m['host']) && ! empty($m['password'])) {
            config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => $m['host'], 'mail.mailers.smtp.port' => (int) $m['port'], 'mail.mailers.smtp.scheme' => (int) $m['port'] === 465 ? 'smtps' : 'smtp', 'mail.mailers.smtp.username' => $m['username'], 'mail.mailers.smtp.password' => $m['password'], 'mail.mailers.smtp.timeout' => 10, 'mail.mailers.smtp.require_tls' => true, 'mail.from.address' => $m['from'], 'mail.from.name' => 'IsaBlue']);
        }
    }

    public static function googleReady(): bool
    {
        return config('services.google.enabled') && config('services.google.client_id') && config('services.google.client_secret');
    }

    public static function mailReady(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array']);
    }
}
