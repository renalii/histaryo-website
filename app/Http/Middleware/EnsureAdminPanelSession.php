<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('uid')) {
            return redirect()->route('login');
        }

        if ($request->session()->get('role') === 'landmark_manager') {
            $request->session()->put('role', 'site_manager');
        }

        if ($request->session()->get('role') === 'site_manager') {
            return redirect()->route('sitemanager.dashboard');
        }

        if ($request->session()->get('role') !== 'admin') {
            return redirect()->route('login');
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
