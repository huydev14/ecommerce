<form id="form-edit-stock" action="{{ route('stocks.update', $stock->id) }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf
    @method('PUT')

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('stock.edit_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('stock.edit_subtitle') }}</p>
        </div>

        <button type="submit" id="submit-edit-stock"
            class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
            {{ __('stock.save_edit') }}
        </button>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="product_variant_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('stock.variant') }} <span class="tw-text-red-500">*</span>
            </label>
            <select name="product_variant_id" id="product_variant_id" required
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                <option value="">{{ __('stock.variant_placeholder') }}</option>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}" @selected($stock->product_variant_id == $variant->id)>{{ trim(($variant->product?->name ? $variant->product->name . ' - ' : '') . $variant->sku) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="warehouse_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('stock.warehouse') }} <span class="tw-text-red-500">*</span>
            </label>
            <select name="warehouse_id" id="warehouse_id" required
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                <option value="">{{ __('stock.warehouse_placeholder') }}</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected($stock->warehouse_id == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
            <div>
                <label for="quantity" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('stock.quantity') }} <span class="tw-text-red-500">*</span></label>
                <input type="number" min="0" name="quantity" id="quantity" required value="{{ $stock->quantity }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>
            <div>
                <label for="reserved_quantity" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('stock.reserved_quantity') }} <span class="tw-text-red-500">*</span></label>
                <input type="number" min="0" name="reserved_quantity" id="reserved_quantity" required value="{{ $stock->reserved_quantity }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>
            <div>
                <label for="low_stock_threshold" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('stock.low_stock_threshold') }} <span class="tw-text-red-500">*</span></label>
                <input type="number" min="0" name="low_stock_threshold" id="low_stock_threshold" required value="{{ $stock->low_stock_threshold }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>
        </div>
    </div>
</form>
