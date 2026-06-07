<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('customers.edit')
        <button type="button" title="{{ __('customer.action_labels.edit') }}" class="edit-customer-btn user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('customers.edit', $customer->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('customers.remove')
        <button type="button" title="{{ __('customer.action_labels.delete') }}" class="delete-customer-btn user-action-btn tw-text-red-800"
            data-delete-url="{{ route('customers.destroy', $customer->id) }}"
            data-restore-url="{{ route('customers.restore', $customer->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
