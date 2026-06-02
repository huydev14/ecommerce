@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="orderTable" />

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_orderStatus">{{ __('order.status') }}</x-label-small>
                        <x-filter-select id="f_orderStatus" :placeholder="__('order.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_paymentStatus">{{ __('order.payment_status') }}</x-label-small>
                        <x-filter-select id="f_paymentStatus" :placeholder="__('order.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_paymentMethod">{{ __('order.payment_method') }}</x-label-small>
                        <x-filter-select id="f_paymentMethod" :placeholder="__('order.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_customer">{{ __('order.customer') }}</x-label-small>
                        <x-filter-select id="f_customer" :placeholder="__('order.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="orderTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('order.order_number') }}</th>
                        <th>{{ __('order.customer') }}</th>
                        <th>{{ __('order.status') }}</th>
                        <th>{{ __('order.payment_status') }}</th>
                        <th>{{ __('order.payment_method') }}</th>
                        <th>{{ __('order.item_count') }}</th>
                        <th>{{ __('order.total_amount') }}</th>
                        <th>{{ __('order.created_at') }}</th>
                        <th><div class="tw-text-center">{{ __('order.action') }}</div></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="order-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/order.js')
    @endpush
@endsection
