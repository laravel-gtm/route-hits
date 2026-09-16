# Route Hits

Records which authenticated users (by email) view which routes in your Laravel apps, from which IP, and shows a small dashboard of the results. Drop it into any internal tool. Point several apps at one shared database to compare them.

## Install

Published on Packagist as `laravel-gtm/route-hits`. Source lives at `github.com/laravel-gtm/route-hits`.

### 1. Require and migrate

```bash
composer require laravel-gtm/route-hits
php artisan migrate
```

The service provider is auto-discovered. Nothing to register.

To develop against a local checkout instead, add a path repository to the application's `composer.json` before requiring:

```json
"repositories": [
    { "type": "path", "url": "../route-hits" }
]
```

### 2. Set environment variables

Minimum for a standalone app: nothing. `APP_NAME` is used as the app label and hits go to the default database.

To share one `route_hits` table across several apps, add a connection to each app's `config/database.php` (for example `tracking`) pointing at the shared database, then in each app's `.env`:

```dotenv
ROUTE_HITS_APP=billing            # unique per app
ROUTE_HITS_CONNECTION=tracking    # same value in every app
```

Run `php artisan migrate` from one app only. The migration reads `ROUTE_HITS_CONNECTION`, so it creates the table in the shared database.

### 3. Gate the dashboard

Open in `local`. Everywhere else, define the gate in `app/Providers/AppServiceProvider.php`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewRouteHits', fn (User $user) => str_ends_with($user->email, '@laravel.com'));
}
```

### 4. Create the queue

Outside `local`, hits are dispatched to the `route_tracking` queue. On Laravel Cloud, open the environment, click **Add compute**, then **Managed queue**, name it `route_tracking`, standard type, smallest Flex size. Deploy. Cloud sets `QUEUE_CONNECTION=cloud` for you.

Elsewhere, make sure a worker listens on that queue:

```bash
php artisan queue:work --queue=route_tracking,default
```

### 5. Make sure the scheduler runs

Pruning is registered in the scheduler. Laravel Cloud has a scheduler toggle on the environment. Elsewhere, `php artisan schedule:run` must run every minute via cron.

### 6. Verify

Load any page as a logged-in user, then visit `/route-hits`. Your app should appear with one hit. If nothing appears, check the queue worker is processing `route_tracking` and that the table exists on the configured connection:

```bash
php artisan queue:work --queue=route_tracking --once
php artisan tinker --execute="echo LaravelGtm\RouteHits\RouteHit::count();"
```

## Configure

Everything is driven by environment variables. Publish the config only if you need more.

| Variable | Default | Purpose |
| --- | --- | --- |
| `ROUTE_HITS_APP` | `APP_NAME` | Name stored on every hit. Distinguishes apps in a shared table. |
| `ROUTE_HITS_CONNECTION` | app default | Database connection holding `route_hits`. Set the same value in every app to share one table. |
| `ROUTE_HITS_QUEUE` | `route_tracking` | Queue name for the recording job. Ignored in `local`, which uses the default queue. |
| `ROUTE_HITS_RETENTION_DAYS` | `90` | Hits older than this are pruned daily. |
| `ROUTE_HITS_PATH` | `route-hits` | Dashboard URI prefix. |

## Queues and Laravel Cloud

Outside `local`, the recording job is dispatched to the `route_tracking` queue on your default queue connection. On Laravel Cloud, create a managed queue named `route_tracking` in each environment. Cloud sets `QUEUE_CONNECTION=cloud` on deploy, so nothing else is needed.

Managed standard queues deliver at least once. Each hit's id is a SHA-1 of app, email, method, path, IP, and the UTC second, computed at dispatch. The job inserts with `insertOrIgnore`, so a replayed or double-fired job never creates a duplicate row. Two genuine hits by the same user to the same path within the same second collapse into one. The job retries 3 times with a 10 second backoff.

In `local` the job goes to the default queue, so `QUEUE_CONNECTION=sync` records inline and any local worker picks it up without a `--queue` flag.

## What gets recorded

Successful `GET` requests through the `web` middleware group. Skipped: non-GET, XHR, Livewire, non-2xx responses, and the dashboard itself. Each row stores app, user email (null for guests), method, route name, route URI pattern, actual path, IP, and timestamp. Only the email is stored, since user IDs differ between apps.

## Dashboard

Visit `/route-hits`. It shows whatever is in the connected database: apps ranked by page views, and per app the top routes, top users, and views per day. Range toggle for 7, 30, or 90 days.

Access is open in the `local` environment. Everywhere else, define a gate:

```php
Gate::define('viewRouteHits', fn (User $user) => $user->isAdmin());
```

## Pruning

Uses Laravel's `Prunable`. The package schedules `model:prune --model=LaravelGtm\RouteHits\RouteHit` daily. Make sure `php artisan schedule:run` is running.

## Development

### Setup

```bash
git clone git@github.com:laravel-gtm/route-hits.git
cd route-hits
composer install
npm install
```

Requires PHP 8.4+ and Node for the Tailwind build. No database server: tests and the dev server use SQLite.

### Layout

```
config/route-hits.php          env-driven config, merged into the host app
database/migrations/           route_hits table, honours ROUTE_HITS_CONNECTION
routes/web.php                 dashboard routes, prefixed by ROUTE_HITS_PATH
src/RouteHitsServiceProvider   wires middleware, routes, views, scheduler
src/TrackRouteHit              web middleware; decides what to record, builds the hit and its id
src/RecordRouteHit             queued job; insertOrIgnore on the hit
src/RouteHit                   Eloquent model, Prunable
src/Authorize                  dashboard gate middleware
src/DashboardController        aggregate queries for index and per-app pages
resources/views/               Blade: index, show, and anonymous components
resources/css/app.css          Tailwind source
resources/dist/route-hits.css  compiled CSS, committed, served by the package
tests/                         Pest tests on Orchestra Testbench
testbench.yaml                 config for the local dev server
```

Request flow: `TrackRouteHit::terminate` runs after the response is sent, filters the request, builds the hit array, hashes it into an id, dispatches `RecordRouteHit`. The job inserts the row. The dashboard only reads aggregates.

### Tests

```bash
composer test
```

Tests run against in-memory SQLite through Testbench. `tests/TestCase.php` registers the provider, sets `route-hits.app` to `billing`, and defines a few routes to hit. Add new tests to `tests/TrackRouteHitTest.php` or a sibling file; `tests/Pest.php` binds every file in `tests/` to that base case.

### Run the dashboard locally

```bash
touch /tmp/route-hits.sqlite
vendor/bin/testbench migrate
vendor/bin/testbench serve
```

Open http://127.0.0.1:8000/route-hits. `testbench.yaml` sets `APP_ENV=local`, so the gate is open and hits process on the sync queue. Seed some data first:

```bash
vendor/bin/testbench tinker --execute='
foreach (["billing" => 120, "crm" => 60, "hr" => 25] as $app => $n) {
    for ($i = 0; $i < $n; $i++) {
        $hit = [
            "app" => $app,
            "email" => ["devon@example.com", "sam@example.com", null][rand(0, 2)],
            "method" => "GET",
            "route_name" => null,
            "uri" => $uri = ["invoices/{id}", "dashboard", "reports"][rand(0, 2)],
            "path" => "/".str_replace("{id}", rand(1, 99), $uri),
            "ip" => "10.0.0.".rand(1, 20),
            "created_at" => now()->utc()->subMinutes(rand(0, 29 * 24 * 60))->startOfSecond(),
        ];
        LaravelGtm\RouteHits\RouteHit::query()->insertOrIgnore(["id" => LaravelGtm\RouteHits\TrackRouteHit::key($hit), ...$hit]);
    }
}'
```

### Editing views

Tailwind classes are compiled from `resources/views`. After changing any Blade file:

```bash
npm run build
```

Commit the regenerated `resources/dist/route-hits.css`. Host apps never run a build.

### Testing inside a real app

Add a path repository to the app's `composer.json` and require the package:

```json
"repositories": [{ "type": "path", "url": "../route-hits" }]
```

```bash
composer require laravel-gtm/route-hits:@dev
```

Composer symlinks the checkout, so edits apply immediately. Run `php artisan migrate` in the app.

### Releasing

Packagist tracks Git tags. Bump nothing in `composer.json`; the tag is the version.

```bash
git tag v1.0.0
git push origin v1.0.0
```

## License

MIT. See [LICENSE.md](LICENSE.md).
