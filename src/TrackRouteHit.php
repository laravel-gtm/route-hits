<?php

namespace LaravelGtm\RouteHits;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackRouteHit
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    // Runs after the response is sent, so recording never slows the page.
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldRecord($request, $response)) {
            return;
        }

        $hit = [
            'app' => config('route-hits.app'),
            'email' => $request->user()?->email,
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'uri' => $request->route()?->uri() ?? $request->path(),
            'path' => '/'.ltrim($request->path(), '/'),
            'ip' => $request->ip(),
            'created_at' => now()->utc()->startOfSecond(),
        ];

        RecordRouteHit::dispatch(['id' => static::key($hit), ...$hit]);
    }

    // Deterministic id: same user, ip, path, and UTC second always produce the
    // same row, so a replayed or double-fired job can never duplicate a hit.
    public static function key(array $hit): string
    {
        return sha1(implode('|', [
            $hit['app'], $hit['email'], $hit['method'], $hit['path'], $hit['ip'],
            $hit['created_at']->format('Y-m-d H:i:s'),
        ]));
    }

    protected function shouldRecord(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->isSuccessful()
            && ! $request->ajax()
            && ! $request->hasHeader('X-Livewire')
            && ! $request->is(config('route-hits.path'), config('route-hits.path').'/*');
    }
}
