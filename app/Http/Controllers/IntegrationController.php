<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Models\OutboundMail;
use App\Services\DatabaseBackup;
use App\Services\Integrations;
use App\Services\MailOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    public function index()
    {
        return view('admin.integrations', ['google' => Integrations::data('google'), 'smtp' => Integrations::data('smtp'), 'pending' => OutboundMail::whereNull('sent_at')->where('expires_at', '>', now())->count(), 'recent' => OutboundMail::latest()->take(15)->get(), 'backup' => DatabaseBackup::latest()]);
    }

    public function google(Request $r)
    {
        $d = $r->validate(['client_id' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9._-]+$/', 'client_secret' => 'nullable|string|max:1000']);
        $old = Integrations::data('google');
        $d['client_secret'] = ($d['client_secret'] ?? null) ?: ($old['client_secret'] ?? '');
        $d['enabled'] = $r->boolean('enabled');
        if ($d['enabled'] && (empty($d['client_id']) || empty($d['client_secret']))) {
            return back()->withErrors(['google' => 'Completa el ID y el secreto de Google antes de activar.']);
        }
        IntegrationSetting::updateOrCreate(['key' => 'google'], ['value' => $d]);

        return back()->with('status', 'Configuración de Google guardada.');
    }

    public function smtp(Request $r)
    {
        $d = $r->validate(['host' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9.-]+$/', 'port' => 'required|in:465,587', 'username' => 'nullable|string|max:255', 'password' => 'nullable|string|max:1000', 'from' => 'nullable|email|max:255']);
        $old = Integrations::data('smtp');
        $d['password'] = ($d['password'] ?? null) ?: ($old['password'] ?? '');
        $d['enabled'] = $r->boolean('enabled');
        if ($d['enabled'] && (empty($d['host']) || empty($d['username']) || empty($d['password']) || empty($d['from']))) {
            return back()->withErrors(['smtp' => 'Completa servidor, usuario, contraseña y correo remitente.']);
        }
        IntegrationSetting::updateOrCreate(['key' => 'smtp'], ['value' => $d]);

        return back()->with('status', 'Servidor de correo guardado. Envía una prueba para comprobar la entrega.');
    }

    public function test(Request $r)
    {
        if (! Integrations::mailReady()) {
            return back()->withErrors(['smtp' => 'Activa y guarda primero el servidor SMTP.']);
        }
        MailOutbox::enqueue('test:'.Str::uuid(), 'test', config('mail.from.address'), ['subject' => 'Prueba de correo de Isablue', 'name' => 'Administrador', 'message' => 'La configuración de correo de Isablue pudo enviar este mensaje.']);
        MailOutbox::process(3);

        return back()->with('status', 'Prueba registrada para el correo remitente. Revisa su bandeja y el estado de los envíos.');
    }

    public function retry()
    {
        OutboundMail::whereNull('sent_at')->where('expires_at', '>', now())->whereNull('locked_at')->update(['attempts' => 0, 'available_at' => now(), 'last_error' => null]);
        MailOutbox::process(3);

        return back()->with('status', 'Se reintentaron los avisos pendientes.');
    }

    public function backup()
    {
        DatabaseBackup::create();

        return back()->with('status', 'Respaldo de MySQL creado y verificado.');
    }

    public function download()
    {
        return DatabaseBackup::download();
    }
}
