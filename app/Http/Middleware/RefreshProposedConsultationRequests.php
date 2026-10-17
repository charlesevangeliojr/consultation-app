<?php

namespace App\Http\Middleware;

use App\Support\ProposedConsultationRequests;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshProposedConsultationRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        ProposedConsultationRequests::reconcile($request->user());
        return $next($request);
    }
}
