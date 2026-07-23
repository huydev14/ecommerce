@php
    $canUpdateOrder = auth()->user()?->can('orders.update');
    $visibleItemCount = 5;

    $paymentStatusStyles = [
        'pending' => 'tw-text-gray-600',
        'unpaid' => 'tw-text-amber-700',
        'paid' => 'tw-text-emerald-700',
    ];

    $carriers = [
        [
            'code' => 'ghn',
            'name' => 'Giao Hàng Nhanh',
            'badge' => 'GHN',
            'color' => 'tw-bg-blue-600',
            'eta' => 'Giao 1-2 ngày',
            'fee' => 'Phí từ 22.000đ',
        ],
        [
            'code' => 'ghtk',
            'name' => 'Giao Hàng Tiết Kiệm',
            'badge' => 'GHTK',
            'color' => 'tw-bg-emerald-600',
            'eta' => 'Giao 2-4 ngày',
            'fee' => 'Phí từ 15.000đ',
        ],
        [
            'code' => 'jt',
            'name' => 'J&T Express',
            'badge' => 'J&T',
            'color' => 'tw-bg-red-600',
            'eta' => 'Giao 2-3 ngày',
            'fee' => 'Phí từ 18.000đ',
        ],
        [
            'code' => 'spx',
            'name' => 'Shopee Express',
            'badge' => 'SPX',
            'color' => 'tw-bg-orange-600',
            'eta' => 'Giao 3-5 ngày',
            'fee' => 'Phí từ 12.000đ',
        ],
        [
            'code' => 'vtp',
            'name' => 'Viettel Post',
            'badge' => 'VTP',
            'color' => 'tw-bg-orange-500',
            'eta' => 'Giao 3-5 ngày',
            'fee' => 'Phí từ 12.000đ',
        ],
    ];

    $zaloLink = $order->customer_phone ? 'https://zalo.me/' . preg_replace('/\D/', '', $order->customer_phone) : null;
@endphp

<form id="form-update-order" action="{{ route('orders.update', $order->id) }}" method="POST"
    class="tw-flex tw-min-h-0 tw-flex-1 tw-flex-col" novalidate>
    @csrf
    @method('PUT')

    <div
        class="tw-flex tw-items-start tw-justify-between tw-gap-4 tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <div class="tw-flex tw-items-center tw-gap-2">
                <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ $order->order_number }}
                </h3>

                <div
                    class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded-full tw-px-2.5 tw-py-1 tw-text-xs tw-font-medium {{ $currentStatusStyle['bg'] }} {{ $currentStatusStyle['text'] }}">
                    <span class="tw-h-1.5 tw-w-1.5 tw-rounded-full {{ $currentStatusStyle['dot'] }}"></span>
                    @if ($canUpdateOrder)
                        <select name="status" id="status" required aria-label="{{ __('order.status') }}"
                            class="tw-appearance-none tw-border-none tw-bg-transparent tw-p-0 tw-text-xs tw-font-medium tw-outline-none focus:tw-ring-0 {{ $currentStatusStyle['text'] }}">
                            @foreach ($statusOptions as $option)
                                <option value="{{ $option['id'] }}" @selected($order->status === $option['id'])>{{ $option['text'] }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        @php $currentStatusLabel = collect($statusOptions)->firstWhere('id', $order->status)['text'] ?? __('order.unknown'); @endphp
                        <span>{{ $currentStatusLabel }}</span>
                    @endif
                </div>
            </div>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">
                {{ __('order.detail_subtitle', ['date' => optional($order->created_at)->format('d/m/Y H:i')]) }}
            </p>
        </div>

        <div class="tw-flex tw-flex-shrink-0 tw-items-center tw-gap-2">
            @can('orders.update')
                <button type="submit" id="submit-update-order" class="fluent-btn-submit">
                    {{ __('order.save_update') }}
                </button>
            @endcan
            <button type="button" title="{{ __('order.print') }}" onclick="window.print()"
                class="user-action-btn tw-border tw-border-gray-200 tw-text-gray-500">
                <i class="fas fa-print"></i>
            </button>
            <button type="button" title="{{ __('order.close') }}" onclick="ModalHelper.close('modal')"
                class="user-action-btn tw-border tw-border-gray-200 tw-text-gray-500">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>

    <div class="tw-min-h-0 tw-flex-1 tw-overflow-y-auto tw-bg-gray-50 tw-px-6 tw-py-5">
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-5 tw-items-start">
            <div class="lg:tw-col-span-2 tw-space-y-5">

                {{-- Items --}}
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-4">
                    <div class="tw-mb-3 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">
                        {{ __('order.items') }}</div>
                    <div class="tw-overflow-x-auto">
                        <table class="tw-w-full tw-table-fixed tw-divide-y tw-divide-gray-100 tw-text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="tw-py-2 tw-pr-4 tw-w-[40%] tw-text-left tw-font-semibold tw-text-gray-500">
                                        {{ __('order.product') }}</th>
                                    <th
                                        class="tw-py-2 tw-px-2 tw-w-[16%] tw-text-right tw-font-semibold tw-text-gray-500">
                                        {{ __('order.price') }}</th>
                                    <th
                                        class="tw-py-2 tw-px-2 tw-w-[12%] tw-text-right tw-font-semibold tw-text-gray-500">
                                        {{ __('order.quantity') }}</th>
                                    <th
                                        class="tw-py-2 tw-px-2 tw-w-[14%] tw-text-right tw-font-semibold tw-text-gray-500">
                                        {{ __('order.weight') }}</th>
                                    <th
                                        class="tw-py-2 tw-pl-2 tw-w-[18%] tw-text-right tw-font-semibold tw-text-gray-500">
                                        {{ __('order.line_total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tw-divide-y tw-divide-gray-50">
                                @foreach ($order->items as $index => $item)
                                    <tr @if ($index >= $visibleItemCount) class="order-item-extra tw-hidden" @endif>
                                        <td class="tw-py-3 tw-pr-4">
                                            <div class="tw-flex tw-items-start tw-gap-2.5">
                                                @if ($item->product?->optimized_thumbnail_url)
                                                    <img src="{{ $item->product->optimized_thumbnail_url }}"
                                                        alt="{{ $item->product_name }}"
                                                        class="tw-h-10 tw-w-10 tw-flex-shrink-0 tw-rounded tw-border tw-border-gray-200 tw-object-cover" />
                                                @else
                                                    <span
                                                        class="tw-flex tw-h-10 tw-w-10 tw-flex-shrink-0 tw-items-center tw-justify-center tw-rounded tw-border tw-border-gray-200 tw-bg-gray-50 tw-text-gray-300">
                                                        <i class="fas fa-image"></i>
                                                    </span>
                                                @endif
                                                <div class="tw-min-w-0">
                                                    <div class="tw-truncate tw-font-medium tw-text-gray-900">
                                                        {{ $item->product_name }}</div>
                                                    <div class="tw-truncate tw-text-xs tw-text-gray-400">
                                                        {{ $item->product_sku ?: '---' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="tw-py-3 tw-px-2 tw-whitespace-nowrap tw-text-right tw-text-gray-700">
                                            {{ number_format((float) $item->price, 0, ',', '.') }} ₫</td>
                                        <td class="tw-py-3 tw-px-2 tw-text-right tw-text-gray-700">
                                            {{ $item->quantity }}</td>
                                        <td class="tw-py-3 tw-px-2 tw-text-right tw-text-gray-400">-</td>
                                        <td
                                            class="tw-py-3 tw-pl-2 tw-whitespace-nowrap tw-text-right tw-font-medium tw-text-gray-900">
                                            {{ number_format((float) $item->total_price, 0, ',', '.') }} ₫</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($order->items->count() > $visibleItemCount)
                        <button type="button" id="toggle-order-items" data-expanded="0"
                            data-show-more="{{ __('order.show_more', ['count' => $order->items->count() - $visibleItemCount]) }}"
                            data-show-less="{{ __('order.show_less') }}"
                            onclick="
                                const expanded = this.dataset.expanded === '1';
                                document.querySelectorAll('.order-item-extra').forEach((row) => row.classList.toggle('tw-hidden', expanded));
                                this.dataset.expanded = expanded ? '0' : '1';
                                this.textContent = expanded ? this.dataset.showMore : this.dataset.showLess;
                            "
                            class="tw-mt-2 tw-text-xs tw-font-medium tw-text-blue-600 hover:tw-underline">
                            {{ __('order.show_more', ['count' => $order->items->count() - $visibleItemCount]) }}
                        </button>
                    @endif

                    <dl class="tw-mt-4 tw-space-y-1.5 tw-border-t tw-border-gray-100 tw-pt-3 tw-text-sm">
                        <div class="tw-flex tw-justify-between">
                            <dt class="tw-text-gray-500">{{ __('order.subtotal') }}</dt>
                            <dd class="tw-font-medium tw-text-gray-900">
                                {{ number_format((float) $order->subtotal, 0, ',', '.') }} ₫</dd>
                        </div>
                        <div class="tw-flex tw-justify-between">
                            <dt class="tw-text-gray-500">{{ __('order.total_weight') }}</dt>
                            <dd class="tw-font-medium tw-text-gray-900">-</dd>
                        </div>
                        <div class="tw-flex tw-justify-between">
                            <dt class="tw-text-gray-500">{{ __('order.shipping_fee') }}</dt>
                            <dd class="tw-font-medium tw-text-gray-900">
                                {{ number_format((float) $order->shipping_fee, 0, ',', '.') }} ₫</dd>
                        </div>
                        <div class="tw-flex tw-justify-between tw-border-t tw-border-dashed tw-border-gray-200 tw-pt-2 tw-text-base">
                            <dt class="tw-font-semibold tw-text-gray-900">{{ __('order.total_amount') }}</dt>
                            <dd class="tw-font-semibold">
                                {{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫</dd>
                        </div>
                    </dl>

                    <div class="tw-mt-4 tw-border-t tw-border-gray-100 tw-pt-3">
                        <x-label for="notes">{{ __('order.notes') }}</x-label>
                        @if ($order->notes)
                            <p class="tw-text-sm tw-text-gray-700">{{ $order->notes }}</p>
                        @else
                            <p class="tw-text-sm tw-italic tw-text-gray-400">{{ __('order.notes_placeholder') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Fulfillment / confirm order --}}
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-4">
                    <div class="tw-mb-3 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">
                        {{ __('order.fulfillment_title') }}</div>

                    <span class="fluent-label">{{ __('order.fulfillment_method_label') }}</span>
                    <div class="tw-grid tw-grid-cols-2 tw-gap-3">
                        <label class="tw-cursor-pointer">
                            <input type="radio" name="fulfillment_method" value="pickup" class="tw-peer tw-sr-only"
                                checked>
                            <div
                                class="tw-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-600 tw-transition-colors peer-checked:tw-border-gray-900 peer-checked:tw-text-gray-900">
                                <x-icon-truck class="tw-h-4 tw-w-4" /> {{ __('order.fulfillment_pickup') }}
                            </div>
                        </label>
                        <label class="tw-cursor-pointer">
                            <input type="radio" name="fulfillment_method" value="self" class="tw-peer tw-sr-only">
                            <div
                                class="tw-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-100 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-400 tw-transition-colors peer-checked:tw-border-gray-900 peer-checked:tw-text-gray-900">
                                <x-icon-box class="tw-h-4 tw-w-4" /> {{ __('order.fulfillment_self') }}
                            </div>
                        </label>
                    </div>
                    <p class="tw-mt-2 tw-flex tw-items-start tw-gap-1.5 tw-text-xs tw-text-gray-400">
                        <i class="fas fa-circle-info tw-mt-0.5"></i>
                        <span>{{ __('order.fulfillment_pickup_hint') }}</span>
                    </p>

                    <div class="tw-mt-4">
                        <span class="fluent-label">{{ __('order.shipping_unit') }}</span>
                        <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 tw-gap-3">
                            @foreach ($carriers as $index => $carrier)
                                <label class="tw-cursor-pointer">
                                    <input type="radio" name="carrier" value="{{ $carrier['code'] }}"
                                        class="tw-peer tw-sr-only">
                                    <div
                                        class="tw-flex tw-items-center tw-gap-3 tw-rounded-[4px] tw-border tw-border-gray-200 tw-px-3 tw-py-2 tw-transition-colors hover:tw-border-gray-300 peer-checked:tw-border-blue-500 peer-checked:tw-ring-1 peer-checked:tw-ring-blue-500">
                                        <span
                                            class="tw-flex tw-h-8 tw-w-8 tw-flex-shrink-0 tw-items-center tw-justify-center tw-rounded tw-text-[10px] tw-font-bold tw-text-white {{ $carrier['color'] }}">{{ $carrier['badge'] }}</span>
                                        <span class="tw-min-w-0">
                                            <span
                                                class="tw-block tw-truncate tw-text-sm tw-text-gray-900">{{ $carrier['name'] }}</span>
                                            <span class="tw-block tw-text-xs tw-text-gray-400">{{ $carrier['eta'] }} ·
                                                {{ $carrier['fee'] }}</span>
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="button"
                        onclick="fluentToast({type: 'info', title: '{{ __('order.feature_in_progress_title') }}', description: '{{ __('order.feature_in_progress_description') }}', actionType: 'close'})"
                        class="fluent-btn-submit tw-mt-4 tw-flex tw-w-full tw-items-center tw-justify-center tw-gap-2 tw-py-2.5">
                        <i class="fas fa-circle-check"></i> {{ __('order.confirm_order_btn') }}
                    </button>
                </div>

                {{-- Action history --}}
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-4">
                    <div class="tw-mb-3 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">
                        {{ __('order.action_history') }}</div>

                    @forelse ($activities as $activity)
                        <div
                            class="tw-flex tw-gap-3 tw-py-2.5 @if (!$loop->last) tw-border-b tw-border-gray-50 @endif">
                            <span
                                class="tw-mt-1.5 tw-h-2 tw-w-2 tw-flex-shrink-0 tw-rounded-full tw-bg-blue-500"></span>
                            <div class="tw-min-w-0 tw-flex-1">
                                <div class="tw-text-sm tw-text-gray-700">{{ $activity->description }}</div>
                                <div class="tw-mt-0.5 tw-text-xs tw-text-gray-400">
                                    {{ $activity->causer?->name ?? __('order.unknown') }} ·
                                    {{ $activity->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="tw-text-sm tw-italic tw-text-gray-400">{{ __('order.no_history') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="tw-space-y-5">
                {{-- Customer --}}
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-4">
                    <div class="tw-mb-3 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">
                        {{ __('order.customer') }}</div>
                    <div class="tw-flex tw-items-start tw-gap-3">
                        <img src="{{ asset('adminlte/dist/img/avatar-default.jpg') }}"
                            alt="{{ $order->customer_name }}"
                            class="tw-h-9 tw-w-9 tw-flex-shrink-0 tw-rounded-full tw-border tw-border-gray-200 tw-object-cover" />
                        <div class="tw-min-w-0 tw-space-y-1 tw-text-sm tw-text-gray-700">
                            <div class="tw-font-semibold tw-text-gray-900">{{ $order->customer_name }}</div>
                            <div class="tw-text-xs tw-text-gray-400">{{ __('order.customer') }}</div>
                        </div>
                    </div>
                    <div class="tw-mt-3 tw-space-y-1.5 tw-border-t tw-border-gray-100 tw-pt-3 tw-text-sm">
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <span class="tw-text-gray-400">SĐT</span>
                            <span class="tw-text-gray-700">{{ $order->customer_phone }}</span>
                        </div>
                        @if ($zaloLink)
                            <div class="tw-flex tw-items-center tw-justify-between">
                                <span class="tw-text-gray-400">Zalo</span>
                                <a href="{{ $zaloLink }}" target="_blank" rel="noopener"
                                    class="tw-text-blue-600 hover:tw-underline">{{ __('order.contact') }}</a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Shipping address --}}
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-4">
                    <div class="tw-mb-3 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">
                        {{ __('order.shipping_address') }}</div>
                    <div class="tw-space-y-1 tw-text-sm tw-text-gray-700">
                        @foreach ($shippingAddress as $line)
                            <div>{{ $line }}</div>
                        @endforeach
                    </div>
                </div>

                {{-- Payment --}}
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-4">
                    <div class="tw-mb-3 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">
                        {{ __('order.payment_status') }}</div>
                    <dl class="tw-space-y-2 tw-text-sm">
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <dt class="tw-text-gray-500">{{ __('order.payment_method') }}</dt>
                            <dd class="tw-font-medium tw-text-gray-900">{{ $paymentMethodLabel }}</dd>
                        </div>
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <dt class="tw-text-gray-500">{{ __('order.payment_status') }}</dt>
                            <dd>
                                @php $currentPaymentLabel = collect($paymentStatusOptions)->firstWhere('id', $order->payment_status)['text'] ?? __('order.unknown'); @endphp
                                <span
                                    class="tw-font-medium {{ $paymentStatusStyles[$order->payment_status] ?? 'tw-text-gray-700' }}">{{ $currentPaymentLabel }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Cancel order --}}
                <div class="tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-4">
                    <div class="tw-mb-3 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-gray-500">
                        {{ __('order.cancel_order_title') }}</div>
                    <select id="cancel_reason" aria-label="{{ __('order.cancel_order_title') }}"
                        class="tw-mb-3 tw-w-full tw-rounded-[4px] tw-border tw-border-gray-200 tw-bg-white tw-px-2.5 tw-py-1.5 tw-text-sm tw-text-gray-700 focus:tw-outline-none"
                        onchange="
                            document.getElementById('cancel-order-btn').disabled = !this.value;
                            document.getElementById('cancel-reason-other-wrap').classList.toggle('tw-hidden', this.value !== 'other');
                        ">
                        <option value="">{{ __('order.cancel_reason_placeholder') }}</option>
                        @foreach (__('order.cancel_reasons') as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <div id="cancel-reason-other-wrap" class="tw-mb-3 tw-hidden">
                        <x-textarea id="cancel_reason_other" name="cancel_reason_other" rows="5"
                            placeholder="{{ __('order.cancel_reason_other_placeholder') }}" />
                    </div>

                    <button type="button" id="cancel-order-btn" disabled
                        onclick="fluentToast({type: 'info', title: '{{ __('order.feature_in_progress_title') }}', description: '{{ __('order.feature_in_progress_description') }}', actionType: 'close'})"
                        class="tw-w-full tw-rounded-[4px] tw-border tw-border-gray-200 tw-bg-gray-100 tw-px-3 tw-py-1.5 tw-text-sm tw-font-semibold tw-text-gray-600 tw-transition-colors enabled:tw-cursor-pointer enabled:hover:tw-bg-gray-200 disabled:tw-cursor-not-allowed disabled:tw-opacity-70">
                        {{ __('order.cancel_order_btn') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
