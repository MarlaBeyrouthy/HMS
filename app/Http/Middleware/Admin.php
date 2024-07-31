<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
       // $user = Auth::user(); // You can use Auth::user() or $request->user()

        $user = $request->user();

        Log::info('User:', ['user' => $user]); // Log user info

        if ($user) {
            if ($user->permission_id == 2) {
                Log::info('Admin Access Granted');
                return $next($request);
            } else {
                Log::warning('Forbidden Access Attempt by User ID: ' . $user->id);
                return response()->json(['message' => 'forbidden'], 403);
            }
        } else {
            Log::warning('Unauthenticated Access Attempt');
            return response()->json(['message' => 'unauthenticated'], 401);
        }
    }
}
