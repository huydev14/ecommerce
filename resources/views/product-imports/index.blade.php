@extends('layouts.main')

@section('page-header')
    <x-page-header :title="__('product_import.page_title')" :description="__('product_import.page_description')">
        <div>
            <a href="{{ route('products.index') }}"
                class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-700 tw-shadow-sm hover:tw-bg-gray-50">
                <i class="fas fa-arrow-left"></i>
                {{ __('product_import.back_to_products') }}
            </a>
        </div>
    </x-page-header>
@endsection

@section('content')
    @php
        $excelLogo =
            'data:image/svg+xml;base64,' . base64_encode(file_get_contents(resource_path('svg/excel-logo.svg')));
    @endphp

    <div class="tw-h-full tw-px-6 tw-pb-6">
        <div class="tw-flex tw-flex-col tw-gap-5">


            <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden">
                <form action="{{ route('product-imports.upload') }}" method="POST" enctype="multipart/form-data"
                    class="tw-p-6">
                    @csrf

                    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-5">
                        <div class="lg:tw-col-span-4">
                            <label for="warehouse_id" class="tw-mb-2 tw-block tw-text-sm tw-font-medium tw-text-gray-700">
                                Warehouse <span class="tw-text-red-500">*</span>
                            </label>
                            <select id="warehouse_id" name="warehouse_id" required
                                class="tw-w-full tw-rounded-md tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-text-gray-900 tw-shadow-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#107c41]/20">
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

                            <div x-data="{
                                isDropping: false,
                                fileName: '',
                                fileSize: '',
                                hasFile: false,
                                formatFileSize(bytes) {
                                    if (!bytes) {
                                        return '0 KB';
                                    }

                                    const units = ['B', 'KB', 'MB', 'GB'];
                                    const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                                    const size = bytes / Math.pow(1024, unitIndex);

                                    return size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1) + ' ' + units[unitIndex];
                                },
                                setFile(file) {
                                    if (!file) {
                                        return;
                                    }

                                    this.fileName = file.name;
                                    this.fileSize = this.formatFileSize(file.size);
                                    this.hasFile = true;
                                },
                                clearFile() {
                                    this.fileName = '';
                                    this.fileSize = '';
                                    this.hasFile = false;
                                    this.$refs.fileInput.value = '';
                                }
                            }" class="tw-max-w-2xl tw-bg-white tw-rounded-lg tw-py-3">
                                <div x-on:dragover.prevent="isDropping = true" x-on:dragleave.prevent="isDropping = false"
                                    x-on:drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; setFile($refs.fileInput.files[0]);"
                                    x-bind:class="hasFile ? 'tw-bg-emerald-50/60 tw-border-emerald-500 tw-border-solid' : (
                                        isDropping ? 'tw-bg-blue-50 tw-border-blue-400 tw-border-dashed' :
                                        'tw-bg-gray-50 tw-border-gray-300 tw-border-dashed')"
                                    class="tw-relative tw-border-2 tw-rounded-lg tw-min-h-[235px] tw-flex tw-items-center tw-justify-center tw-p-10 tw-text-center tw-transition-colors tw-duration-200">
                                    <input type="file" name="excel_file" x-ref="fileInput" id="excel-upload"
                                        class="tw-hidden" accept=".xls,.xlsx,.csv" required
                                        x-on:change="setFile($refs.fileInput.files[0])">

                                    <div x-show="!hasFile" class="tw-flex tw-flex-col tw-items-center">
                                        <img src="{{ $excelLogo }}" alt="Excel Icon"
                                            class="tw-w-14 tw-h-14 tw-mb-4 tw-object-contain">
                                        <p class="tw-text-gray-600">
                                            {{ __('product_import.drop_file_here') }}
                                            <label for="excel-upload"
                                                class="tw-text-[#0f6cbd] tw-font-semibold tw-cursor-pointer hover:tw-underline">
                                                {{ __('product_import.choose_file') }}
                                            </label>
                                        </p>
                                    </div>

                                    <div x-show="hasFile" class="tw-flex tw-flex-col tw-items-center"
                                        style="display: none;">
                                        <div class="tw-relative tw-mb-4">
                                            <img src="{{ $excelLogo }}" alt="Excel Icon"
                                                class="tw-w-14 tw-h-14 tw-object-contain">

                                        </div>
                                        <p class="tw-text-gray-800">
                                            {{ __('product_import.chosen_file') }}:
                                            <span class="tw-font-bold tw-break-all" x-text="fileName"></span>
                                        </p>
                                        <p class="tw-mt-1 tw-text-sm tw-text-gray-500" x-text="fileSize"></p>
                                        <div class="tw-mt-3 tw-flex tw-items-center tw-justify-center tw-gap-3">
                                            <label for="excel-upload"
                                                class="tw-text-[#0f6cbd] tw-font-semibold tw-cursor-pointer hover:tw-underline">
                                                {{ __('product_import.replace_file') }}
                                            </label>
                                            <button type="button" x-on:click="clearFile"
                                                class="tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-red-200 tw-bg-white tw-text-red-600 tw-shadow-sm hover:tw-bg-red-50"
                                                title="{{ __('product_import.remove_file') }}">
                                                <i class="fas fa-trash tw-text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="tw-flex tw-justify-between tw-mt-2 tw-text-xs tw-text-gray-500 tw-px-1">
                                    <span>{{ __('product_import.supported_formats') }}</span>
                                    <span>{{ __('product_import.maximum_size') }}</span>
                                </div>

                                <div
                                    class="tw-mt-6 tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-flex tw-items-start tw-gap-3">
                                    <img src="{{ $excelLogo }}" alt="Excel" class="tw-w-7 tw-h-7 tw-object-contain">
                                    <div>
                                        <h4 class="tw-font-bold tw-text-gray-800 tw-text-sm">
                                            {{ __('product_import.template_title') }}</h4>
                                        <p class="tw-text-sm tw-text-gray-600 tw-mt-1">
                                            {{ __('product_import.template_description') }}</p>
                                        <a href="{{ route('product-imports.download-template') }}"
                                            class="tw-mt-3 tw-inline-block tw-border tw-border-gray-300 tw-bg-white tw-px-4 tw-py-1.5 tw-rounded-md tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50 tw-shadow-sm">
                                            {{ __('product_import.download') }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                            @error('excel_file')
                                <p class="tw-mt-3 tw-text-sm tw-font-medium tw-text-red-600">{{ $message }}</p>
                            @enderror

                            <div
                                class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center tw-gap-3 tw-justify-end">
                                @can('product-imports.create')
                                    <button type="submit"
                                        class=" tw-justify-center tw-gap-2 tw-rounded-sm tw-bg-[#0f6cbd] tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-white tw-shadow-sm hover:tw-bg-[#115ea3] focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0f6cbd] focus:tw-ring-offset-2">
                                        <i class="fas fa-arrow-right"></i>
                                        {{ __('product_import.upload_preview') }}
                                    </button>
                                @endcan
                            </div>
                        </div>

                        <div class="lg:tw-col-span-8">
                            <section
                                class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm tw-overflow-hidden">
                                <div
                                    class="tw-flex tw-items-center tw-justify-between tw-border-b tw-border-gray-100 tw-px-5 tw-py-4">
                                    <h3 class="tw-text-base tw-font-semibold tw-text-gray-950">
                                        {{ __('product_import.recent_imports') }}
                                    </h3>
                                    <i class="fas fa-clock-rotate-left tw-text-gray-400"></i>
                                </div>

                                <div class="tw-overflow-x-auto">
                                    <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                                        <thead class="tw-bg-gray-50">
                                            <tr>
                                                <th
                                                    class="tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">
                                                    {{ __('product_import.batch_prefix') }}</th>
                                                <th
                                                    class="tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">
                                                    {{ __('product_import.created_at') }}</th>
                                                <th
                                                    class="tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">
                                                    {{ __('product_import.status') }}</th>
                                                <th
                                                    class="tw-px-5 tw-py-3 tw-text-right tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">
                                                    {{ __('product_import.total_rows') }}</th>
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
                                                    <td class="tw-px-5 tw-py-4 tw-text-sm tw-text-gray-600">
                                                        {{ $batch->created_at->format('d/m/Y H:i') }}</td>
                                                    <td class="tw-px-5 tw-py-4">
                                                        <span
                                                            class="tw-inline-flex tw-rounded tw-bg-gray-100 tw-px-2 tw-py-1 tw-text-xs tw-font-medium tw-text-gray-700">{{ $batch->status }}</span>
                                                    </td>
                                                    <td class="tw-px-5 tw-py-4 tw-text-right tw-text-sm tw-text-gray-600">
                                                        {{ number_format($batch->total_rows) }}
                                                        {{ __('product_import.rows_suffix') }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="tw-px-5 tw-py-10 tw-text-center">
                                                        <i class="fas fa-inbox tw-text-2xl tw-text-gray-300"></i>
                                                        <p class="tw-mt-3 tw-text-sm tw-text-gray-500">
                                                            {{ __('product_import.empty_batches') }}</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    </div>
                </form>

                <details class="tw-border-t tw-border-gray-100 tw-bg-gray-50">
                    <summary
                        class="tw-flex tw-cursor-pointer tw-list-none tw-items-center tw-justify-between tw-gap-4 tw-px-6 tw-py-4 hover:tw-bg-gray-100">
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-text-sm">
                            <i class="fas fa-circle-info tw-text-[#0f6cbd]"></i>
                            {{ __('product_import.guide_toggle') }}
                        </span>
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-text-sm tw-font-medium tw-text-[#0f6cbd]">
                            <i class="fas fa-chevron-down tw-text-gray-400"></i>
                        </span>
                    </summary>

                    <div class="tw-border-t tw-border-gray-200 tw-bg-white tw-px-6 tw-py-5">
                        <div class="tw-text-sm tw-text-gray-700">
                            <p>{{ __('product_import.guide_intro_description') }}</p>
                        </div>

                        <div class="tw-mt-5 tw-overflow-x-auto">
                            <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                                <thead>
                                    <tr>
                                        <th
                                            class="tw-w-16 tw-px-2 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-950">
                                            {{ __('product_import.column_index') }}</th>
                                        <th
                                            class="tw-w-[36%] tw-px-2 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-950">
                                            {{ __('product_import.column_name') }}</th>
                                        <th
                                            class="tw-px-2 tw-py-3 tw-text-left tw-text-sm tw-font-semibold tw-text-gray-950">
                                            {{ __('product_import.column_guide') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="tw-divide-y tw-divide-gray-200">
                                    @foreach (__('product_import.guide_rows') as $row)
                                        <tr>
                                            <td class="tw-px-2 tw-py-3 tw-align-top tw-text-sm tw-text-gray-900">
                                                {{ $loop->iteration }}</td>
                                            <td class="tw-px-2 tw-py-3 tw-align-top tw-text-sm tw-text-gray-900">
                                                {{ $row['name'] }}
                                                <span
                                                    class="tw-text-gray-500">({{ __('product_import.' . $row['requirement']) }})</span>
                                            </td>
                                            <td class="tw-px-2 tw-py-3 tw-align-top tw-text-sm tw-text-gray-700">
                                                {{ $row['guide'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            </section>


        </div>
    </div>
@endsection
