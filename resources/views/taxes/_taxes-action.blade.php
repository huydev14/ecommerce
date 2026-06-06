<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('taxes.edit')
        <button id="edit-tax-btn" type="button" title="{{ __('tax.action_labels.edit') }}" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('taxes.edit', $tax->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('taxes.remove')
        <button id="delete-tax-btn" type="button" title="{{ __('tax.action_labels.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('taxes.destroy', $tax->id) }}"
            data-restore-url="{{ route('taxes.restore', $tax->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
