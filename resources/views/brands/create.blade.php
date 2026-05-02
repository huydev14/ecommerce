<form id="form-create-brand" action="{{ route('brands.store') }}" method="POST" enctype="multipart/form-data"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('brand.create_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('brand.create_subtitle') }}</p>
        </div>

        <div>
            <button type="submit" id="submit-create-brand"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                {{ __('brand.save_create') }}
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('brand.brand_name') }} <span class="tw-text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" required placeholder="{{ __('brand.name_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div>
            <label for="website" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('brand.website') }}</label>
            <div class="tw-relative tw-rounded-md tw-shadow-sm">
                <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-3 tw-flex tw-items-center tw-pointer-events-none">
                    <i class="fas fa-globe tw-text-gray-400"></i>
                </div>
                <input type="url" name="website" id="website" placeholder="{{ __('brand.website_placeholder') }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-pl-9 focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>
        </div>

        <div>
            <label for="logo" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('brand.logo_label') }}</label>
            <div
                class="tw-flex tw-items-center tw-gap-4 tw-p-3 tw-border tw-border-dashed tw-border-gray-300 tw-rounded-md tw-bg-gray-50 hover:tw-bg-gray-100 tw-transition-colors">
                <div
                    class="tw-w-14 tw-h-14 tw-shrink-0 tw-rounded tw-border tw-border-gray-200 tw-bg-white tw-flex tw-items-center tw-justify-center tw-overflow-hidden">
                    <img id="logo-preview" src="" alt="{{ __('brand.logo_preview') }}"
                        class="tw-w-full tw-h-full tw-object-contain tw-hidden">
                    <i id="logo-placeholder" class="fas fa-image tw-text-gray-300 tw-text-xl"></i>
                </div>
                <div class="tw-flex-1">
                    <input type="file" name="logo" id="logo" accept="image/png, image/jpeg, image/webp"
                        class="tw-block tw-w-full tw-text-sm tw-text-gray-500 file:tw-mr-4 file:tw-py-1.5 file:tw-px-4 file:tw-rounded-sm file:tw-border-0 file:tw-text-sm file:tw-font-medium file:tw-bg-[#0078D4] file:tw-text-white hover:file:tw-bg-[#106ebe] file:tw-cursor-pointer tw-cursor-pointer tw-transition-colors"
                        onchange="previewBrandLogo(this)">
                    <p class="tw-mt-1.5 tw-text-[11px] tw-text-gray-500">{{ __('brand.logo_hint') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="tw-flex tw-items-center tw-gap-4">
            <x-switch name="is_active" value="0" />
            <div>
                <label for="is_active" class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('brand.active_label') }}</label>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">{{ __('brand.active_hint') }}</p>
            </div>
        </div>
    </div>
</form>
