<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
    @can('users.edit')
        <button id="edit-user-btn" type="button" title="Sửa thông tin" class="user-action-btn tw-text-gray-500 "
            data-edit-url="{{ route('users.edit', $user->id) }}"
            data-slideover-target="slideover-edit-user">
            <x-icon-edit />
        </button>
    @endcan

    @can('users.remove')
        <button id="delete-user-btn" type="button" title="Xóa người dùng" class="user-action-btn tw-text-red-800"
            data-delete-url="{{ route('users.destroy', $user->id) }}"
            data-restore-url="{{ route('users.restore', $user->id) }}">
            <x-icon-delete />
        </button>
    @endcan
</div>
