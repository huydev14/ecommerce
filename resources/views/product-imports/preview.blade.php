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

        <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm">
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

                    <form action="{{ route('product-imports.confirm', $batch->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-bg-[#0f6cbd] tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-shadow-sm hover:tw-bg-[#115ea3] focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0f6cbd] focus:tw-ring-offset-2">
                            <i class="fas fa-check"></i>
                            {{ __('product_import.confirm_import') }}
                        </button>
                    </form>
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

            @if (($missingMasterData['total'] ?? 0) > 0)
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
                <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                    <thead class="tw-bg-gray-50">
                        <tr>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.row') }}</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.category') }}</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.product') }}</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.sku') }}</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.variant_price') }}</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.unit') }}</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.tax') }}</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.status') }}</th>
                            <th class="tw-min-w-[260px] tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.preview_columns.note') }}</th>
                        </tr>
                    </thead>
                    <tbody class="tw-divide-y tw-divide-gray-100 tw-bg-white">
                        @forelse ($rows as $row)
                            <tr class="hover:tw-bg-gray-50">
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-font-medium tw-text-gray-900">#{{ $row->row_number }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ $row->data['product']['category_name'] ?? $row->data['product']['category_id'] ?? '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ $row->data['product']['name'] ?? '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ $row->data['variant']['sku'] ?? '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ isset($row->data['variant']['price']) ? number_format((float) $row->data['variant']['price']) : '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ $row->data['variant']['unit_name'] ?? $row->data['variant']['unit_id'] ?? '-' }}</td>
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
                                <td class="tw-px-5 tw-py-3 tw-text-sm tw-text-gray-600">
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

            <div class="tw-border-t tw-border-gray-100 tw-px-5 tw-py-4">
                {{ $rows->links() }}
            </div>
        </section>
    </div>
@endsection
