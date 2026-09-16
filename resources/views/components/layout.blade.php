<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Route Hits</title>
    <link rel="stylesheet" href="{{ route('route-hits.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="min-h-full text-gray-900">
    <div class="mx-auto max-w-6xl px-6 py-10">
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">
                <a href="{{ route('route-hits.index', ['days' => $days]) }}" class="hover:underline">Route Hits</a>
                @isset($appName) <span class="text-gray-400">/</span> {{ $appName }} @endisset
            </h1>
            <nav class="flex gap-1 rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200">
                @foreach ([7, 30, 90] as $range)
                    <a href="{{ request()->fullUrlWithQuery(['days' => $range]) }}"
                       class="rounded-md px-3 py-1 text-sm {{ $range === $days ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        {{ $range }}d
                    </a>
                @endforeach
            </nav>
        </header>

        {{ $slot }}
    </div>
</body>
</html>
