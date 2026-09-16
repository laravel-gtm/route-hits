<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use LaravelGtm\RouteHits\RecordRouteHit;
use LaravelGtm\RouteHits\RouteHit;
use LaravelGtm\RouteHits\TrackRouteHit;

it('records a GET page view with the user email and route pattern', function () {
    $this->actingAs($this->user())->get('/invoices/42')->assertOk();

    expect(RouteHit::all())->toHaveCount(1);
    expect(RouteHit::first())->toMatchArray([
        'app' => 'billing',
        'email' => 'devon@example.com',
        'method' => 'GET',
        'route_name' => 'invoices.show',
        'uri' => 'invoices/{id}',
        'path' => '/invoices/42',
        'ip' => '127.0.0.1',
    ]);
});

it('records guests with a null email', function () {
    $this->get('/invoices/1');

    expect(RouteHit::first()->email)->toBeNull();
});

it('dispatches to the route_tracking queue outside local', function () {
    Queue::fake();

    $this->get('/invoices/1');

    Queue::assertPushedOn('route_tracking', RecordRouteHit::class);
});

it('dispatches to the default queue in local', function () {
    Queue::fake();
    app()->detectEnvironment(fn () => 'local');

    $this->get('/invoices/1');

    Queue::assertPushed(RecordRouteHit::class, fn ($job) => $job->queue === null);
});

it('is safe to replay the recording job', function () {
    $job = new RecordRouteHit(hit());

    $job->handle();
    $job->handle();

    expect(RouteHit::count())->toBe(1);
});

it('derives the id from user, ip, path, and UTC second', function () {
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00.400', 'UTC'));
    $this->actingAs($this->user())->get('/invoices/42');
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00.900', 'UTC'));
    $this->actingAs($this->user())->get('/invoices/42');
    $this->travelTo(Carbon::parse('2026-09-16 12:00:01', 'UTC'));
    $this->actingAs($this->user())->get('/invoices/42');

    expect(RouteHit::count())->toBe(2);
    expect(RouteHit::first()->id)->toBe(TrackRouteHit::key(hit([
        'path' => '/invoices/42', 'created_at' => now()->utc()->subSecond(),
    ])));
});

it('skips non-GET, ajax, livewire, failed, and dashboard requests', function () {
    $this->post('/invoices');
    $this->get('/invoices/1', ['X-Requested-With' => 'XMLHttpRequest']);
    $this->get('/invoices/1', ['X-Livewire' => 'true']);
    $this->get('/missing');
    $this->get('/route-hits');

    expect(RouteHit::count())->toBe(0);
});

it('prunes hits older than the retention window', function () {
    RouteHit::create(hit(['created_at' => now()->subDays(91)]));
    RouteHit::create(hit(['created_at' => now()->subDays(89)]));

    $this->artisan('model:prune', ['--model' => [RouteHit::class]]);

    expect(RouteHit::count())->toBe(1);
});

it('denies the dashboard outside local without the gate', function () {
    $this->get('/route-hits')->assertForbidden();

    Gate::define('viewRouteHits', fn ($user) => true);

    $this->actingAs($this->user())->get('/route-hits')->assertOk();
});

it('renders apps ranked by hits and the per-app breakdown', function () {
    Gate::define('viewRouteHits', fn ($user) => true);
    foreach (range(1, 3) as $i) {
        RouteHit::create(hit(['path' => "/invoices/$i"]));
    }
    RouteHit::create(hit(['app' => 'crm', 'email' => 'sam@example.com', 'uri' => 'leads']));

    $this->actingAs($this->user())->get('/route-hits')
        ->assertOk()->assertSeeInOrder(['billing', 'crm']);

    $this->actingAs($this->user())->get('/route-hits/crm')
        ->assertOk()->assertSee('GET /leads')->assertSee('sam@example.com')->assertDontSee('devon@example.com');

    $this->actingAs($this->user())->get('/route-hits/route-hits.css')
        ->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
});

function hit(array $overrides = []): array
{
    $hit = [
        'app' => 'billing',
        'email' => 'devon@example.com',
        'method' => 'GET',
        'route_name' => null,
        'uri' => 'invoices/{id}',
        'path' => '/invoices/1',
        'ip' => '127.0.0.1',
        'created_at' => now()->utc()->startOfSecond(),
        ...$overrides,
    ];

    return ['id' => TrackRouteHit::key($hit), ...$hit];
}
