@extends('layouts.main')

@section('page-header')
    <x-page-header :title="__('product_import.preview_title', ['id' => $batch->id])" :description="__('product_import.preview_description')" />
@endsection

@section('content')
    @php
        $statusClass = [
            'ready' => 'tw-bg-[#eff6fc] tw-text-[#0f6cbd]',
            'processing' => 'tw-bg-amber-50 tw-text-amber-700',
            'importing' => 'tw-bg-violet-50 tw-text-violet-700',
            'completed' => 'tw-bg-emerald-50 tw-text-emerald-700',
            'completed_with_errors' => 'tw-bg-red-50 tw-text-red-700',
        ][$batch->status] ?? 'tw-bg-gray-100 tw-text-gray-700';
        $initialProcessedRows = $rows->total();
        $initialTotalRows = max((int) $batch->total_rows, $initialProcessedRows);
        $initialPercentage = $initialTotalRows > 0 ? min(100, (int) round(($initialProcessedRows / $initialTotalRows) * 100)) : 0;
        $showProgress = in_array($batch->status, ['processing', 'preview_ready'], true);
    @endphp

    <div class="tw-h-full tw-overflow-y-auto tw-px-6 tw-pb-6">
        @if (session('error'))
            <div class="tw-mb-4 tw-rounded tw-border tw-border-red-200 tw-bg-red-50 tw-px-4 tw-py-3 tw-text-sm tw-text-red-700">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="tw-mb-4 tw-rounded tw-border tw-border-emerald-200 tw-bg-emerald-50 tw-px-4 tw-py-3 tw-text-sm tw-text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($showProgress)
            <div x-data="{
                batchId: @js((string) $batch->id),
                progressUrl: @js(route('product-imports.progress', $batch->id)),
                processed: @js($initialProcessedRows),
                total: @js($initialTotalRows),
                percentage: @js($initialPercentage),
                status: @js($batch->status),
                isVisible: true,
                refreshTimer: null,
                pollTimer: null,
                updateProgress(event) {
                    this.processed = Number(event.processedRows || 0);
                    this.total = Number(event.totalRows || 0);
                    this.status = event.status || this.status;
                    this.percentage = this.total > 0 ? Math.min(100, Math.round((this.processed / this.total) * 100)) : 0;

                    if ((event.isFinished || this.percentage >= 100) && !this.refreshTimer) {
                        if (this.pollTimer) {
                            clearInterval(this.pollTimer);
                        }

                        this.refreshTimer = setTimeout(() => window.location.reload(), 1000);
                    }
                },
                fetchProgress() {
                    if (!window.axios) {
                        return;
                    }

                    window.axios.get(this.progressUrl).then((response) => {
                        this.updateProgress(response.data || {});
                    });
                },
                init() {
                    if (!window.Echo) {
                        this.fetchProgress();
                        this.pollTimer = setInterval(() => this.fetchProgress(), 1500);
                        return;
                    }

                    window.Echo.channel('import.' + this.batchId)
                        .listen('.progress.updated', (event) => this.updateProgress(event));

                    this.fetchProgress();
                    this.pollTimer = setInterval(() => this.fetchProgress(), 1500);
                }
            }" x-show="isVisible"
                class="tw-mb-5 tw-rounded tw-border tw-border-emerald-200 tw-bg-emerald-50/60 tw-p-5 tw-shadow-sm">
                <div class="tw-flex tw-flex-col tw-gap-4 lg:tw-flex-row lg:tw-items-center lg:tw-justify-between">
                    <div>
                        <h3 class="tw-text-base tw-font-semibold tw-text-gray-950">
                            {{ __('product_import.import_progress_title') }}
                        </h3>
                        <p class="tw-mt-1 tw-text-sm tw-text-gray-600">
                            {{ __('product_import.import_progress_description') }}
                        </p>
                    </div>
                    <div class="tw-grid tw-grid-cols-3 tw-gap-4 tw-text-sm">
                        <div>
                            <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">
                                {{ __('product_import.progress_total') }}
                            </p>
                            <p class="tw-mt-1 tw-text-lg tw-font-semibold tw-text-gray-950" x-text="total">0</p>
                        </div>
                        <div>
                            <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">
                                {{ __('product_import.progress_processed') }}
                            </p>
                            <p class="tw-mt-1 tw-text-lg tw-font-semibold tw-text-gray-950" x-text="processed">0</p>
                        </div>
                        <div>
                            <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">
                                {{ __('product_import.progress_percent') }}
                            </p>
                            <p class="tw-mt-1 tw-text-lg tw-font-semibold tw-text-gray-950">
                                <span x-text="percentage">0</span>%
                            </p>
                        </div>
                    </div>
                </div>

                <div class="tw-mt-4 tw-h-2.5 tw-w-full tw-overflow-hidden tw-rounded-full tw-bg-gray-200">
                    <div class="tw-h-2.5 tw-rounded-full tw-bg-[#107c41] tw-transition-all tw-duration-300"
                        x-bind:style="'width: ' + percentage + '%'"></div>
                </div>
            </div>
        @endif

        <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden">
            <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-center lg:tw-justify-between tw-gap-4 tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                <div>
                    <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                        <span class="tw-inline-flex tw-rounded tw-px-2.5 tw-py-1 tw-text-xs tw-font-semibold {{ $statusClass }}">
                            {{ __('product_import.batch_statuses.' . $batch->status) }}
                        </span>
                        <span class="tw-text-xs tw-text-gray-500">
                            {{ __('product_import.created_at_label', ['time' => $batch->created_at->format('d/m/Y H:i')]) }}
                        </span>
                    </div>
                    <h3 class="tw-mt-3 tw-text-2xl tw-font-semibold tw-text-gray-950">{{ __('product_import.preview_heading') }}</h3>
                    <p class="tw-mt-1 tw-text-sm tw-text-gray-500">{{ __('product_import.preview_hint') }}</p>
                </div>

                <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                    <a href="{{ route('product-imports.index') }}"
                        class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50">
                        <i class="fas fa-arrow-left"></i>
                        {{ __('product_import.upload_another') }}
                    </a>

                    @if ($canCancelImport)
                        <form action="{{ route('product-imports.cancel', $batch->id) }}" method="POST"
                            onsubmit="return confirm(@js(__('product_import.cancel_confirm')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-border tw-border-red-200 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-red-600 tw-shadow-sm hover:tw-bg-red-50 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-red-500 focus:tw-ring-offset-2">
                                <i class="fas fa-trash"></i>
                                {{ __('product_import.cancel_import') }}
                            </button>
                        </form>
                    @endif

                    @if ($canConfirmImport)
                        <form action="{{ route('product-imports.confirm', $batch->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-bg-[#0f6cbd] tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-shadow-sm hover:tw-bg-[#115ea3] focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0f6cbd] focus:tw-ring-offset-2">
                                <i class="fas fa-check"></i>
                                {{ __('product_import.confirm_import') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-border-b tw-border-gray-100">
                <div class="tw-px-6 tw-py-4 md:tw-border-r tw-border-gray-100">
                    <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">{{ __('product_import.total_rows') }}</p>
                    <p class="tw-mt-2 tw-text-2xl tw-font-semibold tw-text-gray-950">{{ number_format($batch->total_rows) }}</p>
                </div>
                <div class="tw-px-6 tw-py-4 md:tw-border-r tw-border-gray-100">
                    <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">{{ __('product_import.valid_rows') }}</p>
                    <p class="tw-mt-2 tw-text-2xl tw-font-semibold tw-text-emerald-700">{{ number_format($validRows) }}</p>
                </div>
                <div class="tw-px-6 tw-py-4">
                    <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">{{ __('product_import.error_rows') }}</p>
                    <p class="tw-mt-2 tw-text-2xl tw-font-semibold tw-text-red-700">{{ number_format($errorRows) }}</p>
                </div>
            </div>

            @if (($missingMasterData['total'] ?? 0) > 0 && $canResolveMasterData)
                <div class="tw-border-b tw-border-amber-100 tw-bg-amber-50 tw-px-6 tw-py-4">
                    <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-center lg:tw-justify-between tw-gap-4">
                        <div>
                            <h4 class="tw-text-sm tw-font-semibold tw-text-amber-900">{{ __('product_import.resolve_title') }}</h4>
                            <p class="tw-mt-1 tw-text-sm tw-text-amber-800">
                                {{ __('product_import.resolve_description', [
                                    'categories' => number_format($missingMasterData['categories']),
                                    'units' => number_format($missingMasterData['units']),
                                    'taxes' => number_format($missingMasterData['taxes']),
                                ]) }}
                            </p>
                        </div>
                        <form action="{{ route('product-imports.resolve-master-data', $batch->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-bg-amber-600 tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-shadow-sm hover:tw-bg-amber-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-amber-500 focus:tw-ring-offset-2">
                                <i class="fas fa-wand-magic-sparkles"></i>
                                {{ __('product_import.resolve_action') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="tw-overflow-x-auto">
                <table class="tw-w-full tw-min-w-[1480px] tw-table-fixed tw-divide-y tw-divide-gray-200">
                    <thead class="tw-bg-gray-50">
                        <tr>
                            <th class="tw-w-[80px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.row') }}</th>
                            <th class="tw-w-[230px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.category') }}</th>
                            <th class="tw-w-[360px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.product') }}</th>
                            <th class="tw-w-[180px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.sku') }}</th>
                            <th class="tw-w-[140px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.variant_price') }}</th>
                            <th class="tw-w-[120px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.unit') }}</th>
                            <th class="tw-w-[100px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.tax') }}</th>
                            <th class="tw-w-[120px] tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.status') }}</th>
                            <th class="tw-w-[350px] tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.note') }}</th>
                        </tr>
                    </thead>
                    <tbody class="tw-divide-y tw-divide-gray-100 tw-bg-white">
                        @forelse ($rows as $row)
                            <tr class="hover:tw-bg-gray-50">
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-font-medium tw-text-gray-900">#{{ $row->row_number }}</td>
                                <td class="tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">
                                    <div class="tw-truncate" title="{{ $row->data['product']['category_name'] ?? $row->data['product']['category_id'] ?? '-' }}">
                                        {{ $row->data['product']['category_name'] ?? $row->data['product']['category_id'] ?? '-' }}
                                    </div>
                                </td>
                                <td class="tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">
                                    <div class="tw-truncate" title="{{ $row->data['product']['name'] ?? '-' }}">
                                        {{ $row->data['product']['name'] ?? '-' }}
                                    </div>
                                </td>
                                <td class="tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">
                                    <div class="tw-truncate" title="{{ $row->data['variant']['sku'] ?? '-' }}">
                                        {{ $row->data['variant']['sku'] ?? '-' }}
                                    </div>
                                </td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ isset($row->data['variant']['price']) ? number_format((float) $row->data['variant']['price']) : '-' }}</td>
                                <td class="tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">
                                    <div class="tw-truncate" title="{{ $row->data['variant']['unit_name'] ?? $row->data['variant']['unit_id'] ?? '-' }}">
                                        {{ $row->data['variant']['unit_name'] ?? $row->data['variant']['unit_id'] ?? '-' }}
                                    </div>
                                </td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ $row->data['variant']['tax'] ?? '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3">
                                    @if ($row->status === 'valid')
                                        <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-emerald-50 tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-emerald-700">
                                            <i class="fas fa-circle-check"></i>
                                            {{ __('product_import.row_status_valid') }}
                                        </span>
                                    @else
                                        <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-red-50 tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-red-700">
                                            <i class="fas fa-circle-exclamation"></i>
                                            {{ __('product_import.row_status_error') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="tw-px-5 tw-py-3 tw-text-sm tw-leading-6 tw-text-gray-600">
                                    {{ $row->error_message ?: __('product_import.ready_to_import') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="tw-px-5 tw-py-10 tw-text-center tw-text-sm tw-text-gray-500">
                                    {{ __('product_import.empty_preview') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tw-border-t tw-border-gray-100 tw-bg-white tw-px-5 tw-py-4">
                <div class="tw-flex tw-flex-col tw-gap-3 sm:tw-flex-row sm:tw-items-center sm:tw-justify-between">
                    <p class="tw-text-sm tw-text-gray-600">
                        {{ __('product_import.pagination_summary', [
                            'first' => number_format($rows->firstItem() ?? 0),
                            'last' => number_format($rows->lastItem() ?? 0),
                            'total' => number_format($rows->total()),
                        ]) }}
                    </p>

                    @if ($rows->hasPages())
                        <nav class="tw-flex tw-flex-wrap tw-items-center tw-gap-1" aria-label="Pagination">
                            @if ($rows->onFirstPage())
                                <span
                                    class="tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded tw-border tw-border-gray-200 tw-bg-gray-50 tw-px-3 tw-text-sm tw-font-medium tw-text-gray-400">
                                    {{ __('product_import.pagination_previous') }}
                                </span>
                            @else
                                <a href="{{ $rows->previousPageUrl() }}"
                                    class="tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50">
                                    {{ __('product_import.pagination_previous') }}
                                </a>
                            @endif

                            @php
                                $startPage = max(1, $rows->currentPage() - 2);
                                $endPage = min($rows->lastPage(), $rows->currentPage() + 2);
                            @endphp

                            @foreach ($rows->getUrlRange($startPage, $endPage) as $page => $url)
                                @if ($page === $rows->currentPage())
                                    <span
                                        class="tw-inline-flex tw-h-9 tw-min-w-9 tw-items-center tw-justify-center tw-rounded tw-bg-[#0f6cbd] tw-px-3 tw-text-sm tw-font-semibold tw-text-white">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}"
                                        class="tw-inline-flex tw-h-9 tw-min-w-9 tw-items-center tw-justify-center tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach

                            @if ($rows->hasMorePages())
                                <a href="{{ $rows->nextPageUrl() }}"
                                    class="tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50">
                                    {{ __('product_import.pagination_next') }}
                                </a>
                            @else
                                <span
                                    class="tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded tw-border tw-border-gray-200 tw-bg-gray-50 tw-px-3 tw-text-sm tw-font-medium tw-text-gray-400">
                                    {{ __('product_import.pagination_next') }}
                                </span>
                            @endif
                        </nav>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
