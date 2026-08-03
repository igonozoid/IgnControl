<?php

use App\Http\Middleware\EnsureModuleAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Rotas de negócio (telas Livewire), separadas do web.php
            // que o Breeze gera, pra evitar conflito quando ele
            // instalar/atualizar esse arquivo.
            if (file_exists(__DIR__.'/../routes/business.php')) {
                require __DIR__.'/../routes/business.php';
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'module' => EnsureModuleAccess::class,
        ]);

        // Confia no proxy do Cloudflare Tunnel (roda no mesmo PC, na porta
        // local), pra o Laravel saber que a requisição chegou como HTTPS
        // mesmo o túnel entregando em HTTP puro pra dentro da LAN. Sem
        // isso, os sócios acessando de fora poderiam ter problema de
        // cookie de sessão e links gerados com http:// em vez de https://.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
