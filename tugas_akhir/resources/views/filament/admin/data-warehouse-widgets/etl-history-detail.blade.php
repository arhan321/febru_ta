<div class="space-y-6">
    {{-- Informasi Batch --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Kode Batch
            </div>
            <div class="font-semibold">
                {{ $run->batch_code ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Pemicu
            </div>
            <div class="font-semibold">
                {{ $run->trigger_type === 'scheduler' ? 'Scheduler' : 'Manual' }}
            </div>
        </div>

        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Dijalankan Oleh
            </div>
            <div class="font-semibold">
                @if ($run->trigger_type === 'scheduler')
                    Sistem
                @else
                    {{ $run->triggeredByUser?->name ?? 'CLI / Sistem' }}
                @endif
            </div>
        </div>

        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Status
            </div>

            @php
                $runStatusClass = match ($run->status) {
                    'success' => 'text-success-600',
                    'failed' => 'text-danger-600',
                    'running' => 'text-warning-600',
                    default => 'text-gray-600',
                };
            @endphp

            <div class="font-semibold {{ $runStatusClass }}">
                {{ strtoupper($run->status) }}
            </div>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Baris Sumber
            </div>
            <div class="text-lg font-semibold">
                {{ number_format($run->source_rows, 0, ',', '.') }}
            </div>
        </div>

        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Baris Target
            </div>
            <div class="text-lg font-semibold">
                {{ number_format($run->target_rows, 0, ',', '.') }}
            </div>
        </div>

        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Durasi
            </div>
            <div class="text-lg font-semibold">
                @if ($run->duration_ms !== null)
                    {{ number_format($run->duration_ms / 1000, 2, ',', '.') }} detik
                @else
                    -
                @endif
            </div>
        </div>

        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Jumlah Tahap
            </div>
            <div class="text-lg font-semibold">
                {{ $run->details->count() }}
            </div>
        </div>
    </div>

    {{-- Error Run --}}
    @if ($run->error_message)
        <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-950">
            <div class="mb-1 font-semibold text-danger-700 dark:text-danger-300">
                Error ETL
            </div>

            <div class="text-sm text-danger-700 dark:text-danger-300">
                {{ $run->error_message }}
            </div>
        </div>
    @endif

    {{-- Detail Tahapan --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left">No</th>
                    <th class="px-4 py-3 text-left">Tahap ETL</th>
                    <th class="px-4 py-3 text-left">Jenis</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Sumber</th>
                    <th class="px-4 py-3 text-right">Target</th>
                    <th class="px-4 py-3 text-right">Durasi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($run->details as $detail)
                    @php
                        $statusClass = match ($detail->status) {
                            'success' => 'text-success-600',
                            'failed' => 'text-danger-600',
                            'rolled_back' => 'text-warning-600',
                            'skipped' => 'text-gray-500',
                            'processing' => 'text-info-600',
                            default => 'text-gray-600',
                        };

                        $statusLabel = match ($detail->status) {
                            'success' => 'Success',
                            'failed' => 'Failed',
                            'rolled_back' => 'Rolled Back',
                            'skipped' => 'Skipped',
                            'processing' => 'Processing',
                            default => ucfirst($detail->status),
                        };
                    @endphp

                    <tr>
                        <td class="px-4 py-3">
                            {{ $detail->step_order }}
                        </td>

                        <td class="px-4 py-3 font-medium">
                            {{ $detail->step_name }}

                            @if ($detail->error_message)
                                <div class="mt-1 text-xs text-danger-600">
                                    {{ $detail->error_message }}
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            {{ $detail->step_type === 'dimension' ? 'Dimensi' : 'Fakta' }}
                        </td>

                        <td class="px-4 py-3 font-semibold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            {{ number_format($detail->source_rows, 0, ',', '.') }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            {{ number_format($detail->target_rows, 0, ',', '.') }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            @if ($detail->duration_ms !== null)
                                {{ number_format($detail->duration_ms / 1000, 2, ',', '.') }} dtk
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            Detail tahapan ETL belum tersedia untuk batch ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>