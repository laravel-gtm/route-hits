<?php

namespace LaravelGtm\RouteHits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class RouteHit extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $keyType = 'string';

    public $incrementing = false;

    public function getConnectionName(): ?string
    {
        return config('route-hits.connection');
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('route-hits.retention_days')));
    }
}
