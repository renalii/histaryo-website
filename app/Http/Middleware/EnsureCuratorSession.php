<?php

namespace App\Http\Middleware;

use App\Services\ActiveLandmarksCatalog;
use App\Services\CuratorAccessibleLandmarks;
use App\Services\FirebaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnsureCuratorSession
{
    public function __construct(private FirebaseService $firebase) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Session::has('uid') || Session::get('role') !== 'curator') {
            if ($request->isMethod('GET')) {
                Session::put('login_redirect', $request->fullUrl());
            }

            return redirect()->route('curators.login');
        }

        $assigned = Session::get('assigned_landmark_id');
        $hasAssignment = is_string($assigned) && trim($assigned) !== '';

        if (! $hasAssignment) {
            if ($request->routeIs('curators.pending-assignment')) {
                return $next($request);
            }

            return redirect()->route('curators.pending-assignment');
        }

        Session::put('browseable_landmark_ids', ActiveLandmarksCatalog::documentIds($this->firebase));
        Session::put('writable_landmark_ids', CuratorAccessibleLandmarks::resolveIds($this->firebase, $assigned));

        return $next($request);
    }
}

