<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CookieToBearer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        //Log::info('CookieToBearer Middleware: Checking for auth_token cookieeeeeeeeeeeee.');

        if ($request->hasCookie('auth_token')) {
            $token = $request->cookie('auth_token');
            
            // Set authorization header in all places Laravel Sanctum checks
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
            $request->headers->set('Authorization', 'Bearer ' . $token);
            $request->server->set('HTTP_AUTHORIZATION', 'Bearer ' . $token);
            //Log::info('TRUT');
        }

        return $next($request);
    }
}
