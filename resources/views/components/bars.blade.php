@props(['rows', 'label', 'href' => null])
@php $max = $rows->max('hits') ?: 1; @endphp
@if ($rows->isEmpty())
    <p class="text-sm text-gray-400">No hits in this range.</p>
@else
    <ul class="space-y-2">
        @foreach ($rows as $row)
            <li>
                <div class="mb-1 flex justify-between text-sm">
                    <span class="truncate font-mono">
                        @if ($href)
                            <a href="{{ $href($row) }}" class="hover:underline">{{ $label($row) }}</a>
                        @else
                            {{ $label($row) }}
                        @endif
                    </span>
                    <span class="ml-4 tabular-nums text-gray-500">{{ number_format($row->hits) }}</span>
                </div>
                <div class="h-2 rounded bg-gray-100">
                    <div class="h-2 rounded bg-indigo-500" style="width: {{ round($row->hits / $max * 100, 1) }}%"></div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
