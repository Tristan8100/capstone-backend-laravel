<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\TokenValidation;
use Doctrine\Common\Lexer\Token;

class ValidateTokenWithUserAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $isValid = TokenValidation::where('token_bearer', $request->bearerToken())
            ->where('user_agent', $request->header('User-Agent'))->exists();

        if (!$isValid) {
            return response()->json([
                'response_code' => 403,
                'status' => 'error',
                'message' => 'Invalid token or device.',
            ], 403);
        }

        return $next($request);
    }
}
