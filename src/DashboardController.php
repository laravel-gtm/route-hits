<?php

namespace LaravelGtm\RouteHits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    public function index(Request $request)
    {
        $days = $this->days($request);
        $since = now()->subDays($days)->startOfDay();

        return view('route-hits::index', [
            'days' => $days,
            'apps' => $this->query($since)->select('app', DB::raw('count(*) as hits'), DB::raw('count(distinct email) as users'))
                ->groupBy('app')->orderByDesc('hits')->get(),
            'daily' => $this->daily($since, $days),
        ]);
    }

    public function show(Request $request, string $app)
    {
        $days = $this->days($request);
        $since = now()->subDays($days)->startOfDay();

        return view('route-hits::show', [
            'days' => $days,
            'appName' => $app,
            'routes' => $this->query($since, $app)->select('method', 'uri', DB::raw('count(*) as hits'))
                ->groupBy('method', 'uri')->orderByDesc('hits')->limit(25)->get(),
            'users' => $this->query($since, $app)->select('email', DB::raw('count(*) as hits'))
                ->groupBy('email')->orderByDesc('hits')->limit(25)->get(),
            'daily' => $this->daily($since, $days, $app),
        ]);
    }

    public function css()
    {
        return response()->file(__DIR__.'/../resources/dist/route-hits.css', ['Content-Type' => 'text/css']);
    }

    protected function days(Request $request): int
    {
        return in_array((int) $request->query('days'), [7, 30, 90]) ? (int) $request->query('days') : 30;
    }

    protected function query($since, ?string $app = null)
    {
        return RouteHit::query()->where('created_at', '>=', $since)
            ->when($app, fn ($q) => $q->where('app', $app));
    }

    // One point per day, zero-filled, so the chart has no gaps.
    protected function daily($since, int $days, ?string $app = null): array
    {
        $counts = $this->query($since, $app)->get(['created_at'])
            ->countBy(fn ($hit) => $hit->created_at->toDateString());

        return collect(range(0, $days - 1))
            ->map(fn ($i) => $since->copy()->addDays($i)->toDateString())
            ->mapWithKeys(fn ($date) => [$date => $counts[$date] ?? 0])
            ->all();
    }
}
