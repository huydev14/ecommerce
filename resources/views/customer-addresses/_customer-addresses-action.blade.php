<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('customer-addresses.edit')
        <button id="edit-customer-address-btn" type="button" title="{{ __('customer_address.action_labels.edit') }}" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('customer-addresses.edit', $address->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('customer-addresses.remove')
        <button id="delete-customer-address-btn" type="button" title="{{ __('customer_address.action_labels.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('customer-addresses.destroy', $address->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
