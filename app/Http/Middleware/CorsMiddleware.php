<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // Yang Asli Dari Perprov
    // public function handle(Request $request, Closure $next): Response
    // {
    //     return $next($request)->header('Access-Control-Allow-Origin', '*')
    //                           ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    // }

    // Function handle diganti Elsa untuk menyesuaikan kebutuhan Export Excel
    public function handle($request, Closure $next)
    {
        // Handle Request Preflight (OPTIONS)
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }

        $response = $next($request);

        // Menggunakan $response->headers->set() agar aman untuk BinaryFileResponse / StreamedResponse
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        
        // Expose Content-Disposition agar React bisa membaca nama file dari header
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');

        return $response;
    }
}
