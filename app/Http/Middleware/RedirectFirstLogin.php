<?php

namespace App\Http\Middleware;

use App\Models\UserActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectFirstLogin
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $hasActivity = UserActivityLog::where('user_id', Auth::id())->exists();

            if (!$hasActivity && !$request->is('admin/get-started-page')) {
                return redirect()->to('/admin/get-started-page?first-visit=1');
            }
        }

        return $next($request);
    }
}
