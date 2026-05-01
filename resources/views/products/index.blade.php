@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            <x-toolbar dataTableInstance="productTable">
                <x-create-button btnId="create-product" />
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">

                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_productName">{{ __('product.filter.product') }}</x-label-small>
                        <x-filter-select id="f_productName" :placeholder="__('product.filter.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_category">{{ __('product.filter.category') }}</x-label-small>
                        <x-filter-select id="f_category" :placeholder="__('product.filter.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_brand">{{ __('product.filter.brand') }}</x-label-small>
                        <x-filter-select id="f_brand" :placeholder="__('product.filter.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_status">{{ __('product.filter.status') }}</x-label-small>
                        <x-filter-select id="f_status" :placeholder="__('product.filter.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="productTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('product.table.name') }}</th>
                        <th>{{ __('product.table.slug') }}</th>
                        <th>{{ __('product.table.category') }}</th>
                        <th>{{ __('product.table.brand') }}</th>
                        <th>{{ __('product.table.status') }}</th>
                        <th>{{ __('product.table.created_at') }}</th>
                        <th>{{ __('product.table.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('product.table.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="product-modal-content"></div>
    </x-modal>

    @push('scripts')
        <script src="{{ asset('js/pages/product.js') }}" defer></script>
        <script type="module">
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: @json(__('product.js.toast.success_title')),
                        description: "{{ session('success') }}",
                        subtitle: @json(__('product.js.code_prefix')) + ' 200',
                        actionType: 'close',
                    });
                @endif

                $('#category_id, #brand_id').select2({
                    theme: 'bootstrap4',
                    minimumResultsForSearch: 5,
                    width: '100%',
                });
            });

            window.ProductRoutes = {
                data: "{{ route('products.data') }}",
                filterData: "{{ route('products.filter_data') }}",
                create: "{{ route('products.create') }}",
            };

            window.ProductI18n = {
                confirmDelete: @json(__('product.js.confirm_delete')),
                deletingTitle: @json(__('product.js.toast.delete_title')),
                deletingDescription: @json(__('product.js.toast.delete_description')),
                undo: @json(__('product.js.undo')),
                undoSuccessTitle: @json(__('product.js.toast.undo_success_title')),
                undoSuccessDescription: @json(__('product.js.toast.undo_success_description')),
                restoreErrorTitle: @json(__('product.js.toast.restore_error_title')),
                restoreErrorDescription: @json(__('product.js.toast.restore_error_description')),
                genericErrorTitle: @json(__('product.js.toast.generic_error_title')),
                genericErrorDescription: @json(__('product.js.toast.generic_error_description')),
                saveLoading: @json(__('product.js.save_loading')),
                processFailedTitle: @json(__('product.js.toast.process_failed_title')),
                processFailedDescription: @json(__('product.js.toast.process_failed_description')),
                systemErrorTitle: @json(__('product.js.toast.system_error_title')),
                systemErrorDescription: @json(__('product.js.toast.system_error_description')),
                codePrefix: @json(__('product.js.code_prefix')),
                successTitle: @json(__('product.js.toast.success_title')),
            };
        </script>
    @endpush
@endsection
