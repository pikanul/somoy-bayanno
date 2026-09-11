<div class="space-y-4">
    @forelse ($revisions as $revision)
        @php
            $changes = $revisionService->compare($revision);
        @endphp

        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        Version {{ $revision->version ?? $revision->revision_number }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $revision->change_summary ?? 'No summary provided' }}
                    </p>
                </div>
                <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                    <p>{{ optional($revision->changedBy ?? $revision->actor)->name ?? 'System' }}</p>
                    <p>{{ optional($revision->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            @if ($changes !== [])
                <dl class="mt-3 space-y-2 text-xs">
                    @foreach (array_slice($changes, 0, 8) as $field => $change)
                        <div>
                            <dt class="font-medium text-gray-700 dark:text-gray-200">{{ str($field)->replace('_', ' ')->title() }}</dt>
                            <dd class="mt-1 grid gap-1 md:grid-cols-2">
                                <span class="rounded bg-gray-50 p-2 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ str((string) json_encode($change['from'], JSON_UNESCAPED_UNICODE))->limit(160) }}</span>
                                <span class="rounded bg-gray-50 p-2 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ str((string) json_encode($change['to'], JSON_UNESCAPED_UNICODE))->limit(160) }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Initial captured version.</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No revisions have been recorded yet.</p>
    @endforelse
</div>
