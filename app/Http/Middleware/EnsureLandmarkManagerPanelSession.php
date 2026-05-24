<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandmarkManagerPanelSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('uid')) {
            return redirect()->route('login');
        }

        if ($request->session()->get('role') === 'landmark_manager') {
            $request->session()->put('role', 'site_manager');
        }

        if ($request->session()->get('role') === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($request->session()->get('role') !== 'site_manager') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
