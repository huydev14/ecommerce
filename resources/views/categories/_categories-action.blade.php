<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('categories.edit')
        <button id="edit-category-btn" type="button" title="{{ __('category.action.edit') }}" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('categories.edit', $category->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('categories.remove')
        <button id="delete-category-btn" type="button" title="{{ __('category.action.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('categories.destroy', $category->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
