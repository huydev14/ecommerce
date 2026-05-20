<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    <button id="edit-unit-btn" type="button" title="{{ __('unit.action_labels.edit') }}" class="user-action-btn tw-text-gray-500"
        data-edit-url="{{ route('units.edit', $unit->id) }}">
        <x-icon-edit />
    </button>

    <button id="delete-unit-btn" type="button" title="{{ __('unit.action_labels.delete') }}" class="user-action-btn tw-text-red-800"
        data-delete-url="{{ route('units.destroy', $unit->id) }}"
        data-restore-url="{{ route('units.restore', $unit->id) }}">
        <x-icon-delete />
    </button>
</div>
