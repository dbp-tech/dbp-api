<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class CheckUserUid
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
        if (!$request->header('user-uid')) return response()->json(resultFunction("Err code M-CUU: user_uid is required"));

        $user = User::with(['hr_employee'])
            ->where('user_uid', $request->header('user-uid'))
            ->first();
        if (!$user) return response()->json(resultFunction("Err code M-CUU: uset not found"));

//        $request->headers->set('company_id', 1);
        $request->headers->set('user', $user);
        return $next($request);
    }
}