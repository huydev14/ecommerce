@php
    $selectedCustomerId = old('customer_id', $address?->customer_id);
    $selectedProvinceId = old('province_id', $address?->province_id);
    $selectedDistrictId = old('district_id', $address?->district_id);
    $selectedWardCode = old('ward_code', $address?->ward_code);
@endphp

<div>
    <label for="customer_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
        {{ __('customer_address.customer') }} <span class="tw-text-red-500">*</span>
    </label>
    <select name="customer_id" id="customer_id" required
        class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        <option value="">{{ __('customer_address.customer_placeholder') }}</option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>
                {{ $customer->fullname ?: $customer->email }}
            </option>
        @endforeach
    </select>
</div>

<div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
    <div>
        <label for="receiver_name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
            {{ __('customer_address.receiver_name') }} <span class="tw-text-red-500">*</span>
        </label>
        <input type="text" name="receiver_name" id="receiver_name" required value="{{ old('receiver_name', $address?->receiver_name) }}"
            class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
    </div>

    <div>
        <label for="receiver_phone" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
            {{ __('customer_address.receiver_phone') }} <span class="tw-text-red-500">*</span>
        </label>
        <input type="text" name="receiver_phone" id="receiver_phone" required value="{{ old('receiver_phone', $address?->receiver_phone) }}"
            class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
    </div>
</div>

<div>
    <label for="specific_address" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
        {{ __('customer_address.specific_address') }} <span class="tw-text-red-500">*</span>
    </label>
    <input type="text" name="specific_address" id="specific_address" required value="{{ old('specific_address', $address?->specific_address) }}"
        placeholder="{{ __('customer_address.specific_address_placeholder') }}"
        class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
</div>

<div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 customer-address-location-fields"
    data-selected-district-id="{{ $selectedDistrictId }}"
    data-selected-ward-code="{{ $selectedWardCode }}">
    <div>
        <label for="province_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
            {{ __('customer_address.province_name') }} <span class="tw-text-red-500">*</span>
        </label>
        <select name="province_id" id="province_id" required
            class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            <option value="">{{ __('customer_address.province_placeholder') }}</option>
            @foreach ($provinces as $province)
                <option value="{{ $province['ProvinceID'] ?? '' }}" @selected((string) $selectedProvinceId === (string) ($province['ProvinceID'] ?? ''))>
                    {{ $province['ProvinceName'] ?? '' }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="province_name" id="province_name" value="{{ old('province_name', $address?->province_name) }}">
    </div>

    <div>
        <label for="district_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
            {{ __('customer_address.district_name') }} <span class="tw-text-red-500">*</span>
        </label>
        <select name="district_id" id="district_id" required @disabled(! $selectedProvinceId)
            class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            <option value="">{{ __('customer_address.district_placeholder') }}</option>
        </select>
        <input type="hidden" name="district_name" id="district_name" value="{{ old('district_name', $address?->district_name) }}">
    </div>

    <div>
        <label for="ward_code" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
            {{ __('customer_address.ward_name') }} <span class="tw-text-red-500">*</span>
        </label>
        <select name="ward_code" id="ward_code" required @disabled(! $selectedDistrictId)
            class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            <option value="">{{ __('customer_address.ward_placeholder') }}</option>
        </select>
        <input type="hidden" name="ward_name" id="ward_name" value="{{ old('ward_name', $address?->ward_name) }}">
    </div>
</div>

<div class="tw-flex tw-items-center tw-gap-4">
    <x-switch name="is_default" value="0" :checked="old('is_default', $address?->is_default)" />
    <div>
        <label for="is_default" class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('customer_address.default_label') }}</label>
        <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">{{ __('customer_address.default_hint') }}</p>
    </div>
</div>
