@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="unitTable">
                @can('units.create')
                    <x-create-button btnId="create-unit" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_unitName">{{ __('unit.unit') }}</x-label-small>
                        <x-filter-select id="f_unitName" :placeholder="__('unit.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="unitTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('unit.name') }}</th>
                        <th>{{ __('unit.short_name') }}</th>
                        <th>{{ __('unit.created_at') }}</th>
                        <th>{{ __('unit.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('unit.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="unit-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/unit.js')
        <script>
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: Lang.get('unit.success_title'),
                        description: "{{ session('success') }}",
                        subtitle: Lang.get('unit.code_prefix') + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
