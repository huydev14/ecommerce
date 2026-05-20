<form id="form-create-unit" action="{{ route('units.store') }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('unit.create_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('unit.create_subtitle') }}</p>
        </div>

        <div>
            <button type="submit" id="submit-create-unit"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                {{ __('unit.save_create') }}
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                {{ __('unit.unit_name') }} <span class="tw-text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" required placeholder="{{ __('unit.name_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div>
            <label for="short_name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('unit.short_name') }}</label>
            <input type="text" name="short_name" id="short_name" placeholder="{{ __('unit.short_name_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>
    </div>
</form>
