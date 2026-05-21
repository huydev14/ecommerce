<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    <button id="delete-stock-movement-btn" type="button" title="{{ __('stock_movement.action_labels.delete') }}" class="user-action-btn tw-text-red-800"
        data-delete-url="{{ route('stock-movements.destroy', $movement->id) }}"
        data-restore-url="{{ route('stock-movements.restore', $movement->id) }}">
        <x-icon-delete />
    </button>
</div>
