<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\PageView;
use Illuminate\Http\Request;

class TrackPageViews
{
    protected const array PAGES_ENABLED = [
        'angels-and-partners'
    ];
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $url = $request->path();

        if (in_array($url, self::PAGES_ENABLED)) {
            PageView::firstOrCreate(
                ['ip_address' => $ip, 'page_url' => $url],
                ['ip_address' => $ip, 'page_url' => $url]
            );
        }

        return $next($request);
    }
}
