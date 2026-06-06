<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('stocks.edit')
        <button id="edit-stock-btn" type="button" title="{{ __('stock.action_labels.edit') }}" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('stocks.edit', $stock->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('stocks.remove')
        <button id="delete-stock-btn" type="button" title="{{ __('stock.action_labels.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('stocks.destroy', $stock->id) }}"
            data-restore-url="{{ route('stocks.restore', $stock->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
