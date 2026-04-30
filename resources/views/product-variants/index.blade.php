@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            <x-toolbar dataTableInstance="productVariantTable">
                <x-create-button btnId="create-product-variant" />
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
                    <h4 class="tw-text-base tw-font-bold tw-text-gray-800">{{ __('product_variant.filter.title') }}</h4>
                    <button id="close-filter-btn" class="tw-text-gray-400 hover:tw-text-gray-700 tw-transition-colors">
                        <i class="fas fa-times tw-text-lg"></i>
                    </button>
                </div>

                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div>
                        <x-label id="f_product" :label="__('product_variant.filter.product')" />
                        <x-filter-select id="f_product" :placeholder="__('product_variant.filter.placeholder')" />
                    </div>
                    <div>
                        <x-label id="f_isActive" :label="__('product_variant.filter.status')" />
                        <x-filter-select id="f_isActive" :placeholder="__('product_variant.filter.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="productVariantTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('product_variant.table.product') }}</th>
                        <th>{{ __('product_variant.table.sku') }}</th>
                        <th>{{ __('product_variant.table.barcode') }}</th>
                        <th>{{ __('product_variant.table.price') }}</th>
                        <th>{{ __('product_variant.table.compare_at_price') }}</th>
                        <th>{{ __('product_variant.table.cost_price') }}</th>
                        <th>{{ __('product_variant.table.position') }}</th>
                        <th>{{ __('product_variant.table.status') }}</th>
                        <th>{{ __('product_variant.table.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('product_variant.table.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="product-variant-modal-content"></div>
    </x-modal>

    @push('scripts')
        <script src="{{ asset('js/pages/product-variant.js') }}"></script>
        <script>
            window.ProductVariantRoutes = {
                data: "{{ route('product-variants.data') }}",
                filterData: "{{ route('product-variants.filter_data') }}",
                create: "{{ route('product-variants.create') }}",
            };

            window.ProductVariantI18n = {
                confirmDelete: @json(__('product_variant.js.confirm_delete')),
                deletingTitle: @json(__('product_variant.js.toast.delete_title')),
                deletingDescription: @json(__('product_variant.js.toast.delete_description')),
                undo: @json(__('product_variant.js.undo')),
                undoSuccessTitle: @json(__('product_variant.js.toast.undo_success_title')),
                undoSuccessDescription: @json(__('product_variant.js.toast.undo_success_description')),
                restoreErrorTitle: @json(__('product_variant.js.toast.restore_error_title')),
                restoreErrorDescription: @json(__('product_variant.js.toast.restore_error_description')),
                genericErrorTitle: @json(__('product_variant.js.toast.generic_error_title')),
                genericErrorDescription: @json(__('product_variant.js.toast.generic_error_description')),
                saveLoading: @json(__('product_variant.js.save_loading')),
                processFailedTitle: @json(__('product_variant.js.toast.process_failed_title')),
                processFailedDescription: @json(__('product_variant.js.toast.process_failed_description')),
                systemErrorTitle: @json(__('product_variant.js.toast.system_error_title')),
                systemErrorDescription: @json(__('product_variant.js.toast.system_error_description')),
                codePrefix: @json(__('product_variant.js.code_prefix')),
                successTitle: @json(__('product_variant.js.toast.success_title')),
            };

            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: @json(__('product_variant.js.toast.success_title')),
                        description: "{{ session('success') }}",
                        subtitle: @json(__('product_variant.js.code_prefix')) + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
