<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    <button id="show-order-btn" type="button" title="{{ __('order.action_labels.show') }}"
        class="tw-inline-flex tw-h-9 tw-w-9 tw-items-center tw-justify-center tw-rounded-lg tw-border tw-border-gray-200 tw-bg-white tw-text-gray-600 tw-transition-colors hover:tw-border-gray-300 hover:tw-bg-gray-100 active:tw-scale-95"
        data-show-url="{{ route('orders.show', $order->id) }}">
        <x-icon-eye />
    </button>
</div>
