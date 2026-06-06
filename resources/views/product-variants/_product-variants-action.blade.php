<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('product-variants.edit')
        <button id="edit-product-variant-btn" type="button" title="{{ __('product_variant.action.edit') }}" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('product-variants.edit', $variant->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('product-variants.remove')
        <button id="delete-product-variant-btn" type="button" title="{{ __('product_variant.action.delete') }}" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('product-variants.destroy', $variant->id) }}"
            data-restore-url="{{ route('product-variants.restore', $variant->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
