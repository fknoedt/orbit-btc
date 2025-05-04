<?php

namespace App\Http\Middleware;

use App\Models\UserActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $actionData = $this->determineAction($request);
            if ($actionData) {
                UserActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => $actionData['action'],
                    'method' => $actionData['method'],
                    'date' => now(),
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Determine the action to log based on the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    private function determineAction(Request $request): ?array
    {
        $method = $request->method();
        $path = $request->path();

        // Normalize path to remove 'admin/' prefix and handle root admin
        $slug = $path === 'admin' ? 'dashboard' : str_replace('admin/', '', $path);

        // Wildcard GET requests for /admin/*
        if ($method === 'GET' && str_starts_with($path, 'admin/') || $path === 'admin') {
            // Skip resource-specific pages (e.g., /admin/users/1/edit, /admin/users/create)
            if ($this->isSkippableGetRequest($slug)) {
                return null;
            }
            // Convert slug to snake_case action (e.g., get-started-page to visited_get_started)
            $action = 'visited_' . Str::snake(str_replace('-', '_', $slug));
            return ['action' => $action, 'method' => 'GET'];
        }

        // Handle non-admin GET requests (e.g., /sandbox)
        if ($method === 'GET' && $path === 'sandbox') {
            return ['action' => 'visited_sandbox', 'method' => 'GET'];
        }

        // Handle Livewire requests (always POST to livewire/update)
        if ($path === 'livewire/update') {
            $components = $request->input('components', []);
            foreach ($components as $component) {
                $snapshot = json_decode($component['snapshot'] ?? '{}', true);
                $calls = $component['calls'] ?? [];

                // Extract resource and action from snapshot and calls
                $resource = $this->getResourceFromSnapshot($snapshot);

                // 🤷‍♂️
                if ($resource === 'alert_management') {
                    if (! empty($component['updates'])) {
                        $actionType = 'upsertted'; // create or update will fall here
                    } else {
                        $actionType = match($snapshot['memo']['method'] ?? $method) {
                            'GET' => null,
                            'POST' => 'created',
                            'PUT', 'patch' => 'updated',
                            'DELETE' => 'deleted',
                        };
                    }
                }

                $actionType = $actionType ?? $this->getActionTypeFromCalls($calls, $snapshot);

                if ($resource && $actionType) {
                    return [
                        'action' => "{$actionType}_{$resource}",
                        'method' => $this->getHttpMethodForAction($actionType),
                    ];
                }
            }
        }

        return null; // Skip logging if no relevant action
    }

    /**
     * Check if a GET request should be skipped (e.g., resource edit/create pages).
     *
     * @param  string  $slug
     * @return bool
     */
    private function isSkippableGetRequest(string $slug): bool
    {
        // Skip resource-specific pages (e.g., users/1/edit, users/create)
        return preg_match('/^(users|roles|data-sources|requests)\/(create|\d+|\d+\/edit)$/', $slug) ||
            // Skip static assets or other non-page routes
            preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/', $slug) ||
            // Skip sub-resources or invalid slugs
            str_contains($slug, '/');
    }

    /**
     * Extract the resource name from the Livewire snapshot.
     *
     * @param  array  $snapshot
     * @return string|null
     */
    private function getResourceFromSnapshot(array $snapshot): ?string
    {
        // Try to get resource from memo.name (e.g., app.filament.resources.data-source-resource.pages.edit-data-source)
        $memo = $snapshot['memo'] ?? [];
        if (isset($memo['name'])) {
            if (preg_match('/resources\.([a-z-]+)-resource/', $memo['name'], $matches)) {
                return Str::snake(str_replace('-', '_', $matches[1]));
            }
            if ($memo['name'] === 'alert-management') {
                return Str::snake('alert_management'); // data pattern
            }
        }

        // Fallback to record.class (e.g., App\Models\DataSource)
        $record = $snapshot['data']['record'][1] ?? null;
        if (isset($record['class'])) {
            $className = class_basename($record['class']);
            return Str::snake($className);
        }

        return null;
    }

    /**
     * Determine the action type (created, updated, deleted) from Livewire calls.
     *
     * @param  array  $calls
     * @param  array  $snapshot
     * @return string|null
     */
    private function getActionTypeFromCalls(array $calls, array $snapshot): ?string
    {
        foreach ($calls as $call) {
            $method = $call['method'] ?? null;
            $params = $call['params'] ?? [];
            $memo = $snapshot['memo'] ?? [];
            $path = $memo['path'] ?? '';

            if ($method === 'create') {
                return 'created';
            }
            if ($method === 'save' && str_contains($path, '/edit')) {
                return 'updated';
            }
            if ($method === 'mountAction' && isset($params[0]) && $params[0] === 'delete') {
                return 'deleted';
            }
        }

        return null;
    }

    /**
     * Map action type to HTTP method for consistency.
     *
     * @param  string  $actionType
     * @return string
     */
    private function getHttpMethodForAction(string $actionType): string
    {
        return match ($actionType) {
            'created' => 'POST',
            'updated' => 'PATCH',
            'deleted' => 'DELETE',
            default => 'POST',
        };
    }
}
