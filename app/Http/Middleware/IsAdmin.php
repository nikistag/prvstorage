<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
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
        if (($request->user()->admin !== 1)) {
            return redirect('/')->with('warning', 'Only admins can perform these actions!!! Contact Prvstorage admin');
        }
        return $next($request);
    }
}
