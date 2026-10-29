<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $frameHosts = collect(config('video.embed_hosts'))
            ->map(fn (string $host) => 'https://'.$host)
            ->implode(' ');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; "
            ."img-src 'self' data: https://image.tmdb.org; media-src 'self' https:; "
            ."connect-src 'self' https:; frame-src 'self' {$frameHosts}; object-src 'none'; base-uri 'self'; frame-ancestors 'self'",
        );

        return $response;
    }
}
