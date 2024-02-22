<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CheckAccessToken
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @paramstring  $role
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!isset($_COOKIE['access_token_dbp_web'])) return response()->json(resultFunction("Err code M-CAT: unauthorized"));
        if (!$_COOKIE['access_token_dbp_web']) return response()->json(resultFunction("Err code M-CAT: unauthorized"));

        try {
            $decoded = JWT::decode($_COOKIE['access_token_dbp_web'], new Key(env('JWT_SECRET'), 'HS256'));
        } catch (\Exception $e) {
            return response()->json(resultFunction("Err code M-CAT: unauthorized"));
        }

        $request->headers->set('customer_id', $decoded->id);
        return $next($request);
    }
}