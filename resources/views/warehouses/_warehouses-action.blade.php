<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('warehouses.edit')
        <button id="edit-warehouse-btn" type="button" title="{{ __('warehouse.action_labels.edit') }}" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('warehouses.edit', $warehouse->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('warehouses.remove')
        <button id="delete-warehouse-btn" type="button" title="{{ __('warehouse.action_labels.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('warehouses.destroy', $warehouse->id) }}"
            data-restore-url="{{ route('warehouses.restore', $warehouse->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
