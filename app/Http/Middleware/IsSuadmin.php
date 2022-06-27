<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsSuadmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (($request->user()->suadmin !== 1)) {
            return redirect('/')->with('warning', 'Only Owner can perform these actions!!! Contact Prvstorage admins');
        }
        return $next($request);
    }
}
