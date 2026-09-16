<x-route-hits::layout :days="$days" :app-name="$appName">
    <div class="grid gap-6">
        <x-route-hits::card title="Page views per day">
            <x-route-hits::daily-chart :daily="$daily" />
        </x-route-hits::card>

        <div class="grid gap-6 md:grid-cols-2">
            <x-route-hits::card title="Top routes">
                <x-route-hits::bars :rows="$routes" :label="fn ($row) => $row->method.' /'.$row->uri" />
            </x-route-hits::card>

            <x-route-hits::card title="Top users">
                <x-route-hits::bars :rows="$users" :label="fn ($row) => $row->email ?? 'guest'" />
            </x-route-hits::card>
        </div>
    </div>
</x-route-hits::layout>
