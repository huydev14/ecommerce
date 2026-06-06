<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('banners.edit')
        <button id="edit-banner-btn" type="button" title="Sửa" class="user-action-btn tw-text-gray-500"
            data-edit-url="{{ route('banners.edit', $banner->id) }}">
            <x-icon-edit />
        </button>
    @endcan

    @can('banners.remove')
        <button id="delete-banner-btn" type="button" title="Xóa" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('banners.destroy', $banner->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
