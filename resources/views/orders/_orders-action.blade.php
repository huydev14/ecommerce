<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    <button id="show-order-btn" type="button" title="{{ __('order.action_labels.show') }}" class="user-action-btn tw-text-gray-500"
        data-show-url="{{ route('orders.show', $order->id) }}">
        <i class="fas fa-eye"></i>
    </button>
</div>
