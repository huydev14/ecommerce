<form id="form-create-stock-movement" action="{{ route('stock-movements.store') }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('stock_movement.create_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('stock_movement.create_subtitle') }}</p>
        </div>

        <button type="submit" id="submit-create-stock-movement"
            class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
            {{ __('stock_movement.save_create') }}
        </button>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="stock_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('stock_movement.stock') }} <span class="tw-text-red-500">*</span>
            </label>
            <select name="stock_id" id="stock_id" required
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                <option value="">{{ __('stock_movement.stock_placeholder') }}</option>
                @foreach ($stocks as $stock)
                    <option value="{{ $stock->id }}">
                        {{ trim(($stock->productVariant?->product?->name ? $stock->productVariant->product->name . ' - ' : '') . ($stock->productVariant?->sku ?: '---') . ($stock->warehouse?->name ? ' / ' . $stock->warehouse->name : '')) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div>
                <label for="type" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('stock_movement.type') }} <span class="tw-text-red-500">*</span>
                </label>
                <select name="type" id="type" required
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                    <option value="in">{{ __('stock_movement.types.in') }}</option>
                    <option value="out">{{ __('stock_movement.types.out') }}</option>
                    <option value="adjustment">{{ __('stock_movement.types.adjustment') }}</option>
                </select>
            </div>

            <div>
                <label for="quantity" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('stock_movement.quantity') }} <span class="tw-text-red-500">*</span>
                </label>
                <input type="number" min="0" name="quantity" id="quantity" required value="1"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>
        </div>

        <div>
            <label for="note" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('stock_movement.note') }}</label>
            <textarea name="note" id="note" rows="2" placeholder="{{ __('stock_movement.note_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none"></textarea>
        </div>
    </div>
</form>
