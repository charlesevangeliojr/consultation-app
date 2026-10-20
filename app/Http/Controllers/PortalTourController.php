<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortalTourController extends Controller
{
    public function complete(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isTeacher() || $user->isStudent(), 403);
        if (! $user->portal_tour_seen_at) {
            $user->forceFill(['portal_tour_seen_at' => now()])->save();
        }

        return response()->noContent();
    }
}
