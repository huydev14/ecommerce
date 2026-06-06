<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('products.edit')
        <button id="edit-product-btn" type="button" title="{{ __('product.action.edit') }}" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('products.edit', $product->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('products.remove')
        <button id="delete-product-btn" type="button" title="{{ __('product.action.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('products.destroy', $product->id) }}"
            data-restore-url="{{ route('products.restore', $product->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
