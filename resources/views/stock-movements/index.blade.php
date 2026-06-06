@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="stockMovementTable">
                @can('stock-movements.create')
                    <x-create-button btnId="create-stock-movement" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_stock">{{ __('stock_movement.stock') }}</x-label-small>
                        <x-filter-select id="f_stock" :placeholder="__('stock_movement.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_type">{{ __('stock_movement.type') }}</x-label-small>
                        <x-filter-select id="f_type" :placeholder="__('stock_movement.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="stockMovementTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('stock_movement.stock') }}</th>
                        <th>{{ __('stock_movement.type') }}</th>
                        <th>{{ __('stock_movement.quantity_changed') }}</th>
                        <th>{{ __('stock_movement.quantity_after') }}</th>
                        <th>{{ __('stock_movement.note') }}</th>
                        <th>{{ __('stock_movement.created_at') }}</th>
                        <th><div class="tw-text-center">{{ __('stock_movement.action') }}</div></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="stock-movement-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/stock-movement.js')
        <script>
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: Lang.get('stock_movement.success_title'),
                        description: "{{ session('success') }}",
                        subtitle: Lang.get('stock_movement.code_prefix') + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
