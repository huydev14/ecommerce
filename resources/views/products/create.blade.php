<form id="form-create-product" action="{{ route('products.store') }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('product.create_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('product.create_subtitle') }}</p>
        </div>

        <div>
            <button type="submit" id="submit-create-product"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                {{ __('product.save_create') }}
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('product.product_name') }} <span class="tw-text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" required placeholder="{{ __('product.name_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div>
            <label for="description" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product.description') }}</label>
            <textarea name="description" id="description" rows="3" placeholder="{{ __('product.description_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none"></textarea>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div>
                <label for="category_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('product.category') }} <span class="tw-text-red-500">*</span>
                </label>
                <select name="category_id" id="category_id" required
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                    <option value="">{{ __('product.category_placeholder') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="brand_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product.brand') }}</label>
                <select name="brand_id" id="brand_id"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                    <option value="">{{ __('product.brand_placeholder') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div>
                <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('product.status') }} <span class="tw-text-red-500">*</span>
                </label>
                <select name="status" id="status" required
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                    <option value="draft">{{ __('product.draft') }}</option>
                    <option value="published">{{ __('product.published') }}</option>
                    <option value="archived">{{ __('product.archived') }}</option>
                </select>
            </div>

            <div class="tw-flex tw-items-start tw-gap-3 tw-rounded-md tw-border tw-border-gray-200 tw-bg-gray-50 tw-px-3 tw-py-3">
                <input type="checkbox" name="is_featured" id="is_featured" value="1"
                    class="tw-mt-1 tw-rounded tw-border-gray-300 tw-text-[#0078D4] focus:tw-ring-[#0078D4]">
                <div>
                    <label for="is_featured" class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('product.featured_label') }}</label>
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">{{ __('product.featured_hint') }}</p>
                </div>
            </div>
        </div>
    </div>
</form>
