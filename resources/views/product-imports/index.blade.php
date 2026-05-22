@extends('layouts.main')

@section('page-header')
    <x-page-header :title="__('product_import.page_title')" :description="__('product_import.page_description')" />
@endsection

@section('content')
    <div class="tw-h-full tw-overflow-y-auto tw-px-6 tw-pb-6">
        <div class="tw-flex tw-flex-col tw-gap-5">
            <div>
                <a href="{{ route('products.index') }}"
                    class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-700 tw-shadow-sm hover:tw-bg-gray-50">
                    <i class="fas fa-arrow-left"></i>
                    {{ __('product_import.back_to_products') }}
                </a>
            </div>

            <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden">
                <form action="{{ route('product-imports.upload') }}" method="POST" enctype="multipart/form-data" class="tw-p-6">
                    @csrf

                    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-5">
                        <div class="lg:tw-col-span-3">
                            <label for="warehouse_id" class="tw-mb-2 tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                                Warehouse
                            </label>
                            <select id="warehouse_id" name="warehouse_id" required
                                class="tw-w-full tw-rounded-md tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-text-gray-900 tw-shadow-sm focus:tw-border-[#0f6cbd] focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0f6cbd]/20">
                                <option value="">Select warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('warehouse_id')
                                <p class="tw-mt-2 tw-text-sm tw-font-medium tw-text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:tw-col-span-9">
                            <div id="product-import-dropzone"
                                class="tw-relative tw-rounded tw-border-2 tw-border-dashed tw-border-gray-300 tw-bg-gray-50 tw-transition hover:tw-border-[#0f6cbd] hover:tw-bg-[#f5faff]">
                                <a href="{{ route('product-imports.download-template') }}"
                                    class="tw-absolute tw-right-4 tw-top-4 tw-z-10 tw-inline-flex tw-items-center tw-gap-2 tw-rounded tw-border tw-border-[#0f6cbd]/20 tw-bg-white tw-px-3 tw-py-1.5 tw-text-xs tw-font-semibold tw-text-[#0f6cbd] tw-shadow-sm hover:tw-border-[#0f6cbd] hover:tw-bg-[#f5faff]">
                                    <i class="fas fa-file-excel"></i>
                                    {{ __('product_import.download_sample') }}
                                </a>

                                <label for="excel_file"
                                    class="tw-flex tw-min-h-[300px] tw-cursor-pointer tw-flex-col tw-items-center tw-justify-center tw-px-6 tw-py-10 tw-text-center">
                                    <span class="tw-flex tw-h-20 tw-w-20 tw-items-center tw-justify-center tw-rounded tw-bg-emerald-50 tw-text-emerald-600 tw-shadow-sm">
                                        <i class="fas fa-file-excel tw-text-4xl"></i>
                                    </span>
                                    <span class="tw-mt-5 tw-max-w-2xl tw-text-xl tw-font-bold tw-text-gray-950">{{ __('product_import.choose_file_title') }}</span>
                                    <span class="tw-mt-2 tw-max-w-xl tw-text-sm tw-text-gray-500">{{ __('product_import.choose_file_hint') }}</span>
                                    <span id="product-import-file-status"
                                        class="tw-mt-5 tw-hidden tw-items-center tw-gap-2 tw-rounded tw-bg-emerald-50 tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-emerald-700">
                                        <i class="fas fa-circle-check"></i>
                                        <span data-file-name></span>
                                    </span>
                                    <span id="product-import-browse-btn"
                                        class="tw-mt-5 tw-inline-flex tw-items-center tw-gap-2 tw-rounded tw-bg-[#0f6cbd] tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white hover:tw-bg-[#115ea3]">
                                        <i class="fas fa-folder-open"></i>
                                        {{ __('product_import.browse_file') }}
                                    </span>
                                    <input id="excel_file" name="excel_file" type="file" accept=".xlsx,.xls,.csv" required class="tw-sr-only">
                                </label>
                            </div>

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

                <details class="tw-border-t tw-border-gray-100 tw-bg-gray-50">
                    <summary class="tw-flex tw-cursor-pointer tw-list-none tw-items-center tw-justify-between tw-gap-4 tw-px-6 tw-py-4 hover:tw-bg-gray-100">
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-900">
                            <i class="fas fa-circle-info tw-text-[#0f6cbd]"></i>
                            {{ __('product_import.guide_toggle') }}
                        </span>
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-text-sm tw-font-medium tw-text-[#0f6cbd]">
                            <i class="fas fa-chevron-down tw-text-gray-400"></i>
                        </span>
                    </summary>

                    <div class="tw-border-t tw-border-gray-200 tw-bg-white tw-px-6 tw-py-5">
                        <div class="tw-text-sm tw-text-gray-700">
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
                </details>
            </section>

            <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-border-b tw-border-gray-100 tw-px-5 tw-py-4">
                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-950">{{ __('product_import.recent_imports') }}</h3>
                    <i class="fas fa-clock-rotate-left tw-text-gray-400"></i>
                </div>

                <div class="tw-overflow-x-auto">
                    <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                        <thead class="tw-bg-gray-50">
                            <tr>
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.batch_prefix') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.created_at') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.status') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-text-right tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">{{ __('product_import.total_rows') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @forelse ($latestBatches as $batch)
                                <tr class="hover:tw-bg-gray-50">
                                    <td class="tw-px-5 tw-py-4">
                                        <a href="{{ route('product-imports.preview', $batch->id) }}"
                                            class="tw-text-sm tw-font-semibold tw-text-[#0f6cbd] hover:tw-text-[#115ea3]">
                                            {{ __('product_import.batch_prefix') }} #{{ $batch->id }}
                                        </a>
                                    </td>
                                    <td class="tw-px-5 tw-py-4 tw-text-sm tw-text-gray-600">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="tw-px-5 tw-py-4">
                                        <span class="tw-inline-flex tw-rounded tw-bg-gray-100 tw-px-2 tw-py-1 tw-text-xs tw-font-medium tw-text-gray-700">{{ $batch->status }}</span>
                                    </td>
                                    <td class="tw-px-5 tw-py-4 tw-text-right tw-text-sm tw-text-gray-600">{{ number_format($batch->total_rows) }} {{ __('product_import.rows_suffix') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="tw-px-5 tw-py-10 tw-text-center">
                                        <i class="fas fa-inbox tw-text-2xl tw-text-gray-300"></i>
                                        <p class="tw-mt-3 tw-text-sm tw-text-gray-500">{{ __('product_import.empty_batches') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            $(function() {
                const $input = $('#excel_file');
                const $dropzone = $('#product-import-dropzone');
                const $status = $('#product-import-file-status');
                const $fileName = $status.find('[data-file-name]');
                const $browseBtn = $('#product-import-browse-btn');

                function setSelectedFile(file) {
                    if (!file) {
                        return;
                    }

                    $dropzone
                        .removeClass('tw-border-gray-300 tw-bg-gray-50')
                        .addClass('tw-border-emerald-500 tw-bg-emerald-50/60');
                    $fileName.text(Lang.get('product_import.selected_file') + ': ' + file.name);
                    $status.removeClass('tw-hidden').addClass('tw-inline-flex');
                    $browseBtn.addClass('tw-hidden');
                }

                $input.on('change', function() {
                    setSelectedFile(this.files && this.files[0]);
                });

                $dropzone.on('dragenter dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $dropzone.addClass('tw-border-[#0f6cbd] tw-bg-[#f5faff]');
                });

                $dropzone.on('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $dropzone.removeClass('tw-border-[#0f6cbd] tw-bg-[#f5faff]');
                });

                $dropzone.on('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $dropzone.removeClass('tw-border-[#0f6cbd] tw-bg-[#f5faff]');

                    const files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;

                    if (files && files.length) {
                        $input[0].files = files;
                        setSelectedFile(files[0]);
                    }
                });
            });
        </script>
    @endpush
@endsection
