<?php

return [
    // Name stored on every hit. Lets many apps share one table.
    'app' => env('ROUTE_HITS_APP', env('APP_NAME', 'Laravel')),

    // Database connection for the route_hits table. Null uses the app default.
    'connection' => env('ROUTE_HITS_CONNECTION'),

    // Queue name for the recording job. Must match your managed queue name in
    // production. Ignored in the local environment, which uses the default queue.
    'queue' => env('ROUTE_HITS_QUEUE', 'route_tracking'),

    // Days to keep hits. Pruned daily by the scheduler.
    'retention_days' => (int) env('ROUTE_HITS_RETENTION_DAYS', 90),

    // URI prefix for the dashboard.
    'path' => env('ROUTE_HITS_PATH', 'route-hits'),
];
