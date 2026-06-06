<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('brands.edit')
        <button id="edit-brand-btn" type="button" title="{{ __('brand.action.edit') }}" class="user-action-btn tw-text-gray-500 "
            data-edit-url="{{ route('brands.edit', $brand->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('brands.remove')
        <button id="delete-brand-btn" type="button" title="{{ __('brand.action.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('brands.destroy', $brand->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
