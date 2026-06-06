@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="taxTable">
                @can('taxes.create')
                    <x-create-button btnId="create-tax" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_taxName">{{ __('tax.tax') }}</x-label-small>
                        <x-filter-select id="f_taxName" :placeholder="__('tax.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="taxTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('tax.name') }}</th>
                        <th>{{ __('tax.rate') }}</th>
                        <th>{{ __('tax.created_at') }}</th>
                        <th>{{ __('tax.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('tax.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="tax-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/tax.js')
        <script>
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: Lang.get('tax.success_title'),
                        description: "{{ session('success') }}",
                        subtitle: Lang.get('tax.code_prefix') + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
