<form id="form-create-customer-address" action="{{ route('customer-addresses.store') }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('customer_address.create_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('customer_address.create_subtitle') }}</p>
        </div>

        <div>
            <button type="submit" id="submit-create-customer-address"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                {{ __('customer_address.save_create') }}
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        @include('customer-addresses.form-fields', ['address' => null, 'provinces' => $provinces])
    </div>
</form>
