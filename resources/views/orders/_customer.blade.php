<div class="tw-flex tw-flex-col tw-gap-0.5">
    <span class="tw-font-medium tw-text-gray-900">{{ $order->customer_name }}</span>
    <span class="tw-text-xs tw-text-gray-500">{{ $order->customer_phone }}</span>
    @if ($order->customer_email)
        <span class="tw-text-xs tw-text-gray-400">{{ $order->customer_email }}</span>
    @endif
</div>
