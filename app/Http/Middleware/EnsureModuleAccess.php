<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que bloqueia a rota se o usuário não tiver, no mínimo, o
 * nível de acesso indicado para o módulo indicado.
 *
 * Uso nas rotas: ->middleware('module:financial,full')
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module, string $minLevel = 'read'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasModuleAccess($module, $minLevel)) {
            abort(403, "Você não tem acesso suficiente ao módulo '{$module}'.");
        }

        return $next($request);
    }
}
