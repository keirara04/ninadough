<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Usage: ->middleware('admin.role:owner'). Returns 403 with a clear
     * message rather than a generic auth failure, so staff understand
     * it's a permissions issue, not a login bug.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== $role) {
            return response()->json([
                'message' => "This action requires the {$role} role.",
            ], 403);
        }

        return $next($request);
    }
}
