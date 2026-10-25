<?php

use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\RunStoreMaintenance;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $m) {
        $m->alias(['admin' => AdminOnly::class]);
        $m->web(append: [AuthenticateSession::class, SecurityHeaders::class, RunStoreMaintenance::class]);
        $m->redirectGuestsTo('/ingresar');
    })
    ->withExceptions(function (Exceptions $e) {})->create();
