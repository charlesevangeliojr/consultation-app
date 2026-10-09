<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        abort_unless($request->user()?->canAccess($module, $action), 403,
            "You are not allowed to {$action} ".strtolower(config("permissions.modules.{$module}.name", $module)).'.');

        return $next($request);
    }
}
