@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="stockTable">
                @can('stocks.create')
                    <x-create-button btnId="create-stock" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_warehouse">{{ __('stock.warehouse') }}</x-label-small>
                        <x-filter-select id="f_warehouse" :placeholder="__('stock.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_variant">{{ __('stock.variant') }}</x-label-small>
                        <x-filter-select id="f_variant" :placeholder="__('stock.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="stockTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('stock.variant') }}</th>
                        <th>{{ __('stock.warehouse') }}</th>
                        <th>{{ __('stock.quantity') }}</th>
                        <th>{{ __('stock.reserved_quantity') }}</th>
                        <th>{{ __('stock.available_quantity') }}</th>
                        <th>{{ __('stock.low_stock_threshold') }}</th>
                        <th>{{ __('stock.updated_at') }}</th>
                        <th><div class="tw-text-center">{{ __('stock.action') }}</div></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="stock-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/stock.js')
        <script>
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: Lang.get('stock.success_title'),
                        description: "{{ session('success') }}",
                        subtitle: Lang.get('stock.code_prefix') + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
