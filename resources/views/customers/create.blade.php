<form id="form-create-customer" method="POST" action="{{ route('customers.store') }}"
    class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0" novalidate>
    @csrf
    @include('customers.form-fields', ['customer' => null, 'tiers' => $tiers])

    <div
        class="tw-flex tw-items-center tw-justify-end tw-gap-3 tw-px-6 tw-py-4 tw-border-t tw-border-gray-200 tw-bg-gray-50">
        <button type="button" class="close-slideover fluent-btn-cancel">{{ __('actions.cancel') }}</button>
        <button type="submit" class="fluent-btn-submit">{{ __('customer.save_create') }}</button>
    </div>
</form>
