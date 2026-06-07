<form id="form-edit-customer" method="POST" action="{{ route('customers.update', $customer->id) }}"
    class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0" novalidate>
    @csrf
    @method('PUT')
    @include('customers.form-fields', ['customer' => $customer, 'tiers' => $tiers])

    <div
        class="tw-flex tw-items-center tw-justify-end tw-gap-3 tw-px-6 tw-py-4 tw-border-t tw-border-gray-200 tw-bg-gray-50">
        <button type="button" class="close-slideover fluent-btn-cancel">{{ __('actions.cancel') }}</button>
        <button type="submit" class="fluent-btn-submit">{{ __('customer.save_edit') }}</button>
    </div>
</form>
