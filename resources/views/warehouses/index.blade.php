@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="warehouseTable">
                @can('warehouses.create')
                    <x-create-button btnId="create-warehouse" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_warehouse">{{ __('warehouse.warehouse') }}</x-label-small>
                        <x-filter-select id="f_warehouse" :placeholder="__('warehouse.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_isActive">{{ __('warehouse.status') }}</x-label-small>
                        <x-filter-select id="f_isActive" :placeholder="__('warehouse.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="warehouseTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('warehouse.name') }}</th>
                        <th>{{ __('warehouse.code') }}</th>
                        <th>{{ __('warehouse.address') }}</th>
                        <th>{{ __('warehouse.status') }}</th>
                        <th>{{ __('warehouse.updated_at') }}</th>
                        <th><div class="tw-text-center">{{ __('warehouse.action') }}</div></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="warehouse-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/warehouse.js')
        <script>
            $(function() {
                @include('partials.fluent-session-toasts')
            });
        </script>
    @endpush
@endsection
