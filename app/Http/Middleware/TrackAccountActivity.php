<?php

namespace App\Http\Middleware;

use App\Support\AccountActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAccountActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($user = $request->user()) {
            AccountActivity::deactivateDormantStudents($user);
            if (! $user->last_seen_at || $user->last_seen_at->lte(now()->subMinute())
                || ($user->last_logged_out_at && $user->last_seen_at->lte($user->last_logged_out_at))) {
                $user->last_seen_at = now();
                $user->saveQuietly();
            }
        }

        return $response;
    }
}
