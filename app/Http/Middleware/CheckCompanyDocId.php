<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;

class CheckCompanyDocId
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
        if (!$request->header('company-doc-id')) return response()->json(resultFunction("Err code M-CCD: company doc id is required"));

        $company = Company::with([])
            ->where('company_doc_id', $request->header('company-doc-id'))
            ->first();
        if (!$company) return response()->json(resultFunction("Err code M-CCD: company doc id not found"));

        $request->headers->set('company_id', $company->id);
        return $next($request);
    }
}