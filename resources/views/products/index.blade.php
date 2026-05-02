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
                        <x-label-small for="f_productName">{{ __('product.product') }}</x-label-small>
                        <x-filter-select id="f_productName" :placeholder="__('product.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_category">{{ __('product.category') }}</x-label-small>
                        <x-filter-select id="f_category" :placeholder="__('product.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_brand">{{ __('product.brand') }}</x-label-small>
                        <x-filter-select id="f_brand" :placeholder="__('product.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_status">{{ __('product.status') }}</x-label-small>
                        <x-filter-select id="f_status" :placeholder="__('product.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="productTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('product.name') }}</th>
                        <th>{{ __('product.slug') }}</th>
                        <th>{{ __('product.category') }}</th>
                        <th>{{ __('product.brand') }}</th>
                        <th>{{ __('product.status') }}</th>
                        <th>{{ __('product.created_at') }}</th>
                        <th>{{ __('product.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('product.action') }}</div>
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
        @vite('resources/js/pages/product.js')
        <script type="module">
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: Lang.get('product.success_title'),
                        description: "{{ session('success') }}",
                        subtitle: Lang.get('product.code_prefix') + ' 200',
                        actionType: 'close',
                    });
                @endif

                $('#category_id, #brand_id').select2({
                    theme: 'bootstrap4',
                    minimumResultsForSearch: 5,
                    width: '100%',
                });
            });

        </script>
    @endpush
@endsection
