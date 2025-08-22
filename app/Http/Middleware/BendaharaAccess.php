<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BendaharaAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $empId): Response
    {
        // Check if the authenticated user is a bendahara
        if (!auth()->check() || auth()->user()->role !== 3 || auth()->user()->nik !== $empId) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini');
        }
        // Proceed with the request if the user is a bendahara
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke halaman ini'], 403);
        }
        return $next($request);
    }
}
