<form id="form-edit-product-variant" action="{{ route('product-variants.update', $variant->id) }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf
    @method('PUT')

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('product_variant.edit_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('product_variant.edit_subtitle') }}</p>
        </div>

        <div>
            <button type="submit" id="submit-edit-product-variant"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                {{ __('product_variant.save_edit') }}
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="product_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('product_variant.product') }} <span class="tw-text-red-500">*</span>
            </label>
            <select name="product_id" id="product_id" required
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                <option value="">{{ __('product_variant.product_placeholder') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected($variant->product_id == $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="sku" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('product_variant.sku') }} <span class="tw-text-red-500">*</span>
            </label>
            <input type="text" name="sku" id="sku" required value="{{ $variant->sku }}"
                placeholder="{{ __('product_variant.sku_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
            <div>
                <label for="price" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('product_variant.price') }} <span class="tw-text-red-500">*</span>
                </label>
                <input type="number" min="0" step="0.01" name="price" id="price" required value="{{ $variant->price }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>

            <div>
                <label for="compare_at_price" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product_variant.compare_at_price') }}</label>
                <input type="number" min="0" step="0.01" name="compare_at_price" id="compare_at_price" value="{{ $variant->compare_at_price }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>

            <div>
                <label for="cost_price" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product_variant.cost_price') }}</label>
                <input type="number" min="0" step="0.01" name="cost_price" id="cost_price" value="{{ $variant->cost_price }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>
        </div>

        <div>
            <label for="attributes" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product_variant.attributes') }}</label>
            <textarea name="attributes" id="attributes" rows="1" placeholder="{{ __('product_variant.attributes_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">{{ $variant->attributes ? json_encode($variant->attributes) : '' }}</textarea>
        </div>

        <div class="tw-flex tw-items-center tw-gap-4">
            <x-switch name="is_active" value="0" :checked="$variant->is_active" />
            <div>
                <label for="is_active" class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('product_variant.active_label') }}</label>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">{{ __('product_variant.active_hint') }}</p>
            </div>
        </div>
    </div>
</form>
