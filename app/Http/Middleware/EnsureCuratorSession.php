<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureCuratorSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('uid') || Session::get('role') !== 'curator') {
            if ($request->isMethod('GET')) {
                Session::put('login_redirect', $request->fullUrl());
            }

            return redirect()->route('curators.login');
        }

        return $next($request);
    }
}

