@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            <x-toolbar dataTableInstance="productVariantTable">
                <x-create-button btnId="create-product-variant" />
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">

                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_isActive">{{ __('product_variant.status') }}</x-label-small>
                        <x-filter-select id="f_isActive" :placeholder="__('product_variant.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="productVariantTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('product_variant.variant_name') }}</th>
                        <th>{{ __('product_variant.sku') }}</th>
                        <th>{{ __('product_variant.price') }}</th>
                        <th>{{ __('product_variant.compare_at_price') }}</th>
                        <th>{{ __('product_variant.cost_price') }}</th>
                        <th>{{ __('product_variant.status') }}</th>
                        <th>{{ __('product_variant.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('product_variant.action') }}</div>
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
        @vite('resources/js/pages/product-variant.js')
        <script>
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: Lang.get('product_variant.success_title'),
                        description: "{{ session('success') }}",
                        subtitle: Lang.get('product_variant.code_prefix') + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
