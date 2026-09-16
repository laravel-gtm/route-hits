<?php

namespace LaravelGtm\RouteHits;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordRouteHit implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public array $attributes)
    {
        // Local has no managed queue; let it flow through the default queue.
        $this->onQueue(app()->isLocal() ? null : config('route-hits.queue'));
    }

    // At-least-once delivery may replay this job. The id is a hash of the hit
    // itself (see TrackRouteHit::key), so a replay is a no-op, not a duplicate.
    public function handle(): void
    {
        RouteHit::query()->insertOrIgnore($this->attributes);
    }
}
