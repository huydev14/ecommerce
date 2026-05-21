<form id="form-edit-warehouse" action="{{ route('warehouses.update', $warehouse->id) }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf
    @method('PUT')

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('warehouse.edit_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('warehouse.edit_subtitle') }}</p>
        </div>

        <button type="submit" id="submit-edit-warehouse"
            class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
            {{ __('warehouse.save_edit') }}
        </button>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div>
                <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('warehouse.name') }} <span class="tw-text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required value="{{ $warehouse->name }}"
                    placeholder="{{ __('warehouse.name_placeholder') }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>

            <div>
                <label for="code" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                    {{ __('warehouse.code') }} <span class="tw-text-red-500">*</span>
                </label>
                <input type="text" name="code" id="code" required value="{{ $warehouse->code }}"
                    placeholder="{{ __('warehouse.code_placeholder') }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>
        </div>

        <div>
            <label for="address" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('warehouse.address') }}</label>
            <textarea name="address" id="address" rows="2" placeholder="{{ __('warehouse.address_placeholder') }}"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">{{ $warehouse->address }}</textarea>
        </div>

        <div class="tw-flex tw-items-center tw-gap-4">
            <x-switch name="is_active" value="1" :checked="$warehouse->is_active" />
            <div>
                <label for="is_active" class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('warehouse.active_label') }}</label>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">{{ __('warehouse.active_hint') }}</p>
            </div>
        </div>
    </div>
</form>
