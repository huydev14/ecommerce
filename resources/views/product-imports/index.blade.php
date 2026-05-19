@extends('layouts.main')

@section('page-header')
    <x-page-header :title="__('product_import.page_title')" :description="__('product_import.page_description')" />
@endsection

@section('content')
    <div class="tw-h-full tw-overflow-y-auto tw-px-6 tw-pb-6">
        <div class="tw-grid tw-grid-cols-1 xl:tw-grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)] tw-gap-5">
            <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden">
                <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-center lg:tw-justify-between tw-gap-4 tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                    <a href="{{ route('products.index') }}"
                        class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50">
                        <i class="fas fa-arrow-left"></i>
                        {{ __('product_import.back_to_products') }}
                    </a>
                </div>

                <form action="{{ route('product-imports.upload') }}" method="POST" enctype="multipart/form-data" class="tw-p-6">
                    @csrf

                    <div class="tw-grid tw-grid-cols-1 tw-gap-5">
                        <div>
                            <label for="excel_file"
                                class="tw-flex tw-min-h-[280px] tw-cursor-pointer tw-flex-col tw-items-center tw-justify-center tw-rounded tw-border-2 tw-border-dashed tw-border-gray-300 tw-bg-gray-50 tw-px-6 tw-py-8 tw-text-center tw-transition hover:tw-border-[#0f6cbd] hover:tw-bg-[#f5faff]">
                                <span class="tw-flex tw-h-14 tw-w-14 tw-items-center tw-justify-center tw-rounded tw-bg-white tw-text-[#0f6cbd] tw-shadow-sm">
                                    <i class="fas fa-cloud-arrow-up tw-text-2xl"></i>
                                </span>
                                <span class="tw-mt-5 tw-text-lg tw-font-semibold tw-text-gray-950">{{ __('product_import.choose_file_title') }}</span>
                                <span class="tw-mt-2 tw-max-w-md tw-text-sm tw-text-gray-500">{{ __('product_import.choose_file_hint') }}</span>
                                <span class="tw-mt-5 tw-inline-flex tw-items-center tw-gap-2 tw-rounded tw-bg-[#0f6cbd] tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-[#115ea3]">
                                    <i class="fas fa-folder-open"></i>
                                    {{ __('product_import.browse_file') }}
                                </span>
                                <input id="excel_file" name="excel_file" type="file" accept=".xlsx,.xls,.csv" required class="tw-sr-only">
                            </label>

                            @error('excel_file')
                                <p class="tw-mt-3 tw-text-sm tw-font-medium tw-text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="tw-mt-5 tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-end tw-gap-3 tw-border-t tw-border-gray-100 tw-pt-5">
                        <button type="submit"
                            class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-bg-[#0f6cbd] tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-shadow-sm hover:tw-bg-[#115ea3] focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0f6cbd] focus:tw-ring-offset-2">
                            <i class="fas fa-arrow-right"></i>
                            {{ __('product_import.upload_preview') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-border-b tw-border-gray-100 tw-px-5 tw-py-4">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-950">{{ __('product_import.recent_imports') }}</h3>
                    <i class="fas fa-clock-rotate-left tw-text-gray-400"></i>
                </div>

                <div class="tw-divide-y tw-divide-gray-100">
                    @forelse ($latestBatches as $batch)
                        <a href="{{ route('product-imports.preview', $batch->id) }}"
                            class="tw-flex tw-items-center tw-justify-between tw-gap-4 tw-px-5 tw-py-4 hover:tw-bg-gray-50">
                            <div>
                                <p class="tw-text-sm tw-font-semibold tw-text-gray-900">{{ __('product_import.batch_prefix') }} #{{ $batch->id }}</p>
                                <p class="tw-mt-0.5 tw-text-xs tw-text-gray-500">{{ $batch->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="tw-text-right">
                                <span class="tw-inline-flex tw-rounded tw-bg-gray-100 tw-px-2 tw-py-1 tw-text-xs tw-font-medium tw-text-gray-700">{{ $batch->status }}</span>
                                <p class="tw-mt-1 tw-text-xs tw-text-gray-500">{{ number_format($batch->total_rows) }} {{ __('product_import.rows_suffix') }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="tw-px-5 tw-py-8 tw-text-center">
                            <i class="fas fa-inbox tw-text-2xl tw-text-gray-300"></i>
                            <p class="tw-mt-3 tw-text-sm tw-text-gray-500">{{ __('product_import.empty_batches') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden xl:tw-col-span-2">
                <div class="tw-px-5 tw-py-5">
                    <h3 class="tw-text-lg tw-font-semibold tw-text-[#24226b]">{{ __('product_import.guide_title') }}</h3>
                    <div class="tw-mt-4 tw-text-sm tw-text-gray-700">
                        <p class="tw-font-semibold tw-text-gray-950">{{ __('product_import.guide_intro_title') }}</p>
                        <p>{{ __('product_import.guide_intro_description') }}</p>
                    </div>

                    <div class="tw-mt-5 tw-overflow-x-auto">
                        <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="tw-w-16 tw-px-2 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-950">{{ __('product_import.column_index') }}</th>
                                    <th class="tw-w-[36%] tw-px-2 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-950">{{ __('product_import.column_name') }}</th>
                                    <th class="tw-px-2 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-950">{{ __('product_import.column_guide') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tw-divide-y tw-divide-gray-200">
                                @foreach (__('product_import.guide_rows') as $row)
                                    <tr>
                                        <td class="tw-px-2 tw-py-3 tw-align-top tw-text-sm tw-text-gray-900">{{ $loop->iteration }}</td>
                                        <td class="tw-px-2 tw-py-3 tw-align-top tw-text-sm tw-text-gray-900">
                                            {{ $row['name'] }}
                                            <span class="tw-text-gray-500">({{ __('product_import.' . $row['requirement']) }})</span>
                                        </td>
                                        <td class="tw-px-2 tw-py-3 tw-align-top tw-text-sm tw-text-gray-700">{{ $row['guide'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
