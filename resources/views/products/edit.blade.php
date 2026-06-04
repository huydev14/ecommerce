<form id="form-edit-product" action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf
    @method('PUT')

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('product.edit_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('product.edit_subtitle') }}</p>
        </div>

        <div>
            <button type="submit" id="submit-edit-product"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                {{ __('product.save_edit') }}
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('product.product_name') }} <span class="tw-text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" required value="{{ $product->name }}" placeholder="{{ __('product.name_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div>
            <label for="description" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product.description') }}</label>
            <textarea name="description" id="description" rows="3" placeholder="{{ __('product.description_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">{{ $product->description }}</textarea>
        </div>

        <div>
            <label for="thumbnail" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product.thumbnail_label') }}</label>
            <div
                class="tw-flex tw-items-center tw-gap-4 tw-p-3 tw-border tw-border-dashed tw-border-gray-300 tw-rounded-md tw-bg-gray-50 hover:tw-bg-gray-100 tw-transition-colors">
                <div
                    class="tw-w-16 tw-h-16 tw-shrink-0 tw-rounded tw-border tw-border-gray-200 tw-bg-white tw-flex tw-items-center tw-justify-center tw-overflow-hidden">
                    @if ($product->thumbnail)
                        <img src="{{ \Illuminate\Support\Str::startsWith($product->thumbnail, ['http://', 'https://']) ? $product->thumbnail : asset('storage/' . $product->thumbnail) }}"
                            alt="{{ __('product.thumbnail_preview') }}" class="tw-w-full tw-h-full tw-object-cover">
                    @else
                        <i class="fas fa-image tw-text-gray-300 tw-text-xl"></i>
                    @endif
                </div>
                <div class="tw-flex-1">
                    <input type="file" name="thumbnail" id="thumbnail" accept="image/png, image/jpeg, image/webp"
                        class="tw-block tw-w-full tw-text-sm tw-text-gray-500 file:tw-mr-4 file:tw-py-1.5 file:tw-px-4 file:tw-rounded-sm file:tw-border-0 file:tw-text-sm file:tw-font-medium file:tw-bg-[#0078D4] file:tw-text-white hover:file:tw-bg-[#106ebe] file:tw-cursor-pointer tw-cursor-pointer tw-transition-colors">
                    <p class="tw-mt-1.5 tw-text-[11px] tw-text-gray-500">{{ __('product.thumbnail_hint') }}</p>
                </div>
            </div>
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
                        <option value="{{ $category->id }}" @selected($product->category_id == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="brand_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('product.brand') }}</label>
                <select name="brand_id" id="brand_id"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
                    <option value="">{{ __('product.brand_placeholder') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected($product->brand_id == $brand->id)>{{ $brand->name }}</option>
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
                    <option value="draft" @selected($product->status === 'draft')>{{ __('product.draft') }}</option>
                    <option value="published" @selected($product->status === 'published')>{{ __('product.published') }}</option>
                    <option value="archived" @selected($product->status === 'archived')>{{ __('product.archived') }}</option>
                </select>
            </div>

            <div class="tw-flex tw-items-start tw-gap-3 tw-rounded-md tw-border tw-border-gray-200 tw-bg-gray-50 tw-px-3 tw-py-3">
                <input type="checkbox" name="is_featured" id="is_featured" value="1" @checked($product->is_featured)
                    class="tw-mt-1 tw-rounded tw-border-gray-300 tw-text-[#0078D4] focus:tw-ring-[#0078D4]">
                <div>
                    <label for="is_featured" class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('product.featured_label') }}</label>
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">{{ __('product.featured_hint') }}</p>
                </div>
            </div>
        </div>
    </div>
</form>
