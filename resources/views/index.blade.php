<x-route-hits::layout :days="$days">
    <div class="grid gap-6">
        <x-route-hits::card title="Page views per day">
            <x-route-hits::daily-chart :daily="$daily" />
        </x-route-hits::card>

        <x-route-hits::card title="Apps">
            <x-route-hits::bars
                :rows="$apps"
                :label="fn ($row) => $row->app.'  ·  '.$row->users.' users'"
                :href="fn ($row) => route('route-hits.show', ['app' => $row->app, 'days' => $days])" />
        </x-route-hits::card>
    </div>
</x-route-hits::layout>
