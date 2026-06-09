@extends('layouts.main')

@section('content')
    <div class="fluent-card">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="customersTable">
                @can('customers.create')
                    <x-create-button btnId="btn-open-create-customer" target="slideover-create-customer" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_customerStatus">{{ __('customer.status') }}</x-label-small>
                        <x-filter-select id="f_customerStatus" :placeholder="__('customer.placeholder')" />
                    </div>

                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_membershipTier">{{ __('customer.membership_tier') }}</x-label-small>
                        <x-filter-select id="f_membershipTier" :placeholder="__('customer.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="customers-table" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('customer.name') }}</th>
                        <th>{{ __('customer.email') }}</th>
                        <th>{{ __('customer.phone') }}</th>
                        <th>{{ __('customer.membership_tier') }}</th>
                        <th>{{ __('customer.points') }}</th>
                        <th>{{ __('customer.email_status') }}</th>
                        <th>{{ __('customer.status') }}</th>
                        <th>{{ __('customer.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('customer.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-slide-over id="slideover-create-customer" title="{{ __('customer.create_title') }}">
        <div id="content-create-customer" class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0"></div>
    </x-slide-over>

    <x-slide-over id="slideover-edit-customer" title="{{ __('customer.edit_title') }}">
        <div id="content-edit-customer" class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0"></div>
    </x-slide-over>
@endsection

@push('scripts')
    @vite('resources/js/pages/customer.js')
    <script type="module">
        $(function() {
            @include('partials.fluent-session-toasts')
        });
    </script>
@endpush
