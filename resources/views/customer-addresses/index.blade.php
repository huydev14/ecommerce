@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="customerAddressTable">
                <x-create-button btnId="create-customer-address" />
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_customer">{{ __('customer_address.customer') }}</x-label-small>
                        <x-filter-select id="f_customer" :placeholder="__('customer_address.placeholder')" />
                    </div>

                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_isDefault">{{ __('customer_address.default_status') }}</x-label-small>
                        <x-filter-select id="f_isDefault" :placeholder="__('customer_address.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="customerAddressTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('customer_address.customer') }}</th>
                        <th>{{ __('customer_address.receiver_name') }}</th>
                        <th>{{ __('customer_address.receiver_phone') }}</th>
                        <th>{{ __('customer_address.full_address') }}</th>
                        <th>{{ __('customer_address.default_status') }}</th>
                        <th>{{ __('customer_address.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('customer_address.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="customer-address-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/customer-address.js')
        <script>
            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: Lang.get('customer_address.success_title'),
                        description: "{{ session('success') }}",
                        subtitle: Lang.get('customer_address.code_prefix') + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
