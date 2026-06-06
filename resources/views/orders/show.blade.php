<form id="form-update-order" action="{{ route('orders.update', $order->id) }}" method="POST"
    class="tw-flex tw-flex-col" novalidate>
    @csrf
    @method('PUT')

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">
                {{ __('order.detail_title', ['number' => $order->order_number]) }}
            </h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">
                {{ __('order.detail_subtitle', ['date' => optional($order->created_at)->format('d/m/Y H:i')]) }}
            </p>
        </div>

        @can('orders.update')
            <button type="submit" id="submit-update-order"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                {{ __('order.save_update') }}
            </button>
        @endcan
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-space-y-6">
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-4">
            <div class="tw-rounded-sm tw-border tw-border-gray-200 tw-p-4">
                <div class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">{{ __('order.customer') }}</div>
                <div class="tw-mt-3 tw-space-y-1 tw-text-sm tw-text-gray-700">
                    <div class="tw-font-semibold tw-text-gray-900">{{ $order->customer_name }}</div>
                    <div>{{ $order->customer_phone }}</div>
                    @if ($order->customer_email)
                        <div>{{ $order->customer_email }}</div>
                    @endif
                </div>
            </div>

            <div class="tw-rounded-sm tw-border tw-border-gray-200 tw-p-4">
                <div class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">{{ __('order.shipping_address') }}</div>
                <div class="tw-mt-3 tw-space-y-1 tw-text-sm tw-text-gray-700">
                    @foreach ($shippingAddress as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            </div>

            <div class="tw-rounded-sm tw-border tw-border-gray-200 tw-p-4">
                <div class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">{{ __('order.summary') }}</div>
                <dl class="tw-mt-3 tw-space-y-2 tw-text-sm">
                    <div class="tw-flex tw-justify-between tw-gap-4">
                        <dt class="tw-text-gray-500">{{ __('order.subtotal') }}</dt>
                        <dd class="tw-font-medium tw-text-gray-900">{{ number_format((float) $order->subtotal, 0, ',', '.') }} ₫</dd>
                    </div>
                    <div class="tw-flex tw-justify-between tw-gap-4">
                        <dt class="tw-text-gray-500">{{ __('order.shipping_fee') }}</dt>
                        <dd class="tw-font-medium tw-text-gray-900">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }} ₫</dd>
                    </div>
                    <div class="tw-flex tw-justify-between tw-gap-4 tw-border-t tw-border-gray-100 tw-pt-2">
                        <dt class="tw-font-semibold tw-text-gray-900">{{ __('order.total_amount') }}</dt>
                        <dd class="tw-font-semibold tw-text-gray-900">{{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
            <div>
                <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('order.status') }} <span class="tw-text-red-500">*</span>
                </label>
                <select name="status" id="status" required @cannot('orders.update') disabled @endcannot
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option['id'] }}" @selected($order->status === $option['id'])>{{ $option['text'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="payment_status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('order.payment_status') }} <span class="tw-text-red-500">*</span>
                </label>
                <select name="payment_status" id="payment_status" required @cannot('orders.update') disabled @endcannot
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                    @foreach ($paymentStatusOptions as $option)
                        <option value="{{ $option['id'] }}" @selected($order->payment_status === $option['id'])>{{ $option['text'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('order.payment_method') }}</label>
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-gray-50 tw-px-3 tw-py-2 tw-text-sm tw-text-gray-700">
                    {{ $paymentMethodLabel }}
                </div>
            </div>
        </div>

        <div>
            <label for="notes" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('order.notes') }}</label>
            <textarea name="notes" id="notes" rows="3" @cannot('orders.update') readonly @endcannot
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">{{ $order->notes }}</textarea>
        </div>

        <div>
            <div class="tw-mb-3 tw-text-sm tw-font-semibold tw-text-gray-900">{{ __('order.items') }}</div>
            <div class="tw-overflow-x-auto tw-rounded-md tw-border tw-border-gray-200">
                <table class="tw-min-w-full tw-divide-y tw-divide-gray-200 tw-text-sm">
                    <thead class="tw-bg-gray-50">
                        <tr>
                            <th class="tw-px-4 tw-py-2 tw-text-left tw-font-semibold tw-text-gray-600">{{ __('order.product') }}</th>
                            <th class="tw-px-4 tw-py-2 tw-text-left tw-font-semibold tw-text-gray-600">{{ __('order.sku') }}</th>
                            <th class="tw-px-4 tw-py-2 tw-text-right tw-font-semibold tw-text-gray-600">{{ __('order.price') }}</th>
                            <th class="tw-px-4 tw-py-2 tw-text-right tw-font-semibold tw-text-gray-600">{{ __('order.quantity') }}</th>
                            <th class="tw-px-4 tw-py-2 tw-text-right tw-font-semibold tw-text-gray-600">{{ __('order.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="tw-divide-y tw-divide-gray-100 tw-bg-white">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="tw-px-4 tw-py-3 tw-text-gray-900">{{ $item->product_name }}</td>
                                <td class="tw-px-4 tw-py-3 tw-text-gray-500">{{ $item->product_sku ?: '---' }}</td>
                                <td class="tw-px-4 tw-py-3 tw-text-right tw-text-gray-700">{{ number_format((float) $item->price, 0, ',', '.') }} ₫</td>
                                <td class="tw-px-4 tw-py-3 tw-text-right tw-text-gray-700">{{ $item->quantity }}</td>
                                <td class="tw-px-4 tw-py-3 tw-text-right tw-font-medium tw-text-gray-900">{{ number_format((float) $item->total_price, 0, ',', '.') }} ₫</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
