<?php

namespace App\Http\Middleware;

use App\Clients\OrbitBtcClient;
use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next)
    {
        // Get the token from the header defined by OrbitBtcClient::AUTH_TOKEN_NAME
        $tokenHeader = 'Authorization';
        $token = $request->header($tokenHeader);

        // Check if token is provided
        if (!$token) {
            return response()->json([
                'error' => 'No token provided',
                'header' => $tokenHeader,
                'request-headers' => $request->headers->all(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Remove "Bearer " prefix if present (for Authorization header compatibility)
        $token = str_replace('Bearer ', '', $token);

        // Find the client by decrypted client_key
        $client = ApiClient::where('active', true)
            ->where('accepting_connections', true)
            ->firstWhere('client_key', $token);

        // Check if client exists and token is valid
        if (!$client) {
            return response()->json([
                'error' => 'Invalid token',
                'header' => $tokenHeader,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Validate client status
        if (! $client->active) {
            return response()->json([
                'error' => "Client disabled",
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Update tracking fields
        $client->increment('total_requests');
        $client->update([
            'last_request' => now(),
            'last_ip' => $request->ip(),
        ]);

        // Attach client to request for downstream use
        $request->setUserResolver(fn () => $client);

        return $next($request);
    }
}
