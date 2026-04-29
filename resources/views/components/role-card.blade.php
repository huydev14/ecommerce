@props(['title', 'userCount' => 0, 'description' => null, 'roleId', 'editUrl' => '#'])

<div
    class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-[8px] tw-p-5 tw-flex tw-flex-col tw-h-full hover:tw-shadow-md hover:tw-border-gray-300 tw-transition-all tw-duration-200 tw-relative tw-group">

    <div class="tw-flex tw-justify-between tw-items-start tw-mb-2">
        <div class="tw-flex tw-items-center tw-gap-2">
            <h3 class="tw-text-[16px] tw-font-semibold tw-text-gray-900">{{ $title }}</h3>
        </div>

        <div class="tw-relative role-dropdown-container">
            <button type="button"
                class="btn-role-dropdown tw-text-gray-400 hover:tw-text-gray-700 tw-w-8 tw-h-8 tw-rounded-[4px] hover:tw-bg-gray-100 tw-flex tw-items-center tw-justify-center tw-transition-colors focus:tw-outline-none">
                <i class="fas fa-ellipsis-v"></i>
            </button>

            <div
                class="role-dropdown-menu tw-hidden tw-absolute tw-right-0 tw-mt-1 tw-w-40 tw-bg-white tw-border tw-border-gray-200 tw-rounded-[4px] tw-shadow-lg tw-z-10 tw-py-1 tw-overflow-hidden">
                <a href="{{ $editUrl }}"
                    class="tw-flex tw-items-center tw-px-4 tw-py-2 tw-text-[13px] tw-text-gray-700 hover:tw-bg-gray-50 tw-transition-colors">
                    <i class="fas fa-pen tw-mr-2.5 tw-text-gray-400 tw-w-3"></i> Sửa thông tin
                </a>
                <button type="button" id="delete-role-btn" data-delete-url="{{ route('roles.destroy', $roleId) }}"
                    onclick="deleteRole({{ $roleId }}, '{{ $title }}')"
                    class="tw-w-full tw-flex tw-items-center tw-px-4 tw-py-2 tw-text-[13px] tw-text-red-600 hover:tw-bg-red-50 tw-transition-colors">
                    <i class="fas fa-trash tw-mr-2.5 tw-text-red-400 tw-w-3"></i> Xóa vai trò
                </button>
            </div>
        </div>
    </div>

    <p
        class="tw-text-[13px] tw-flex-grow tw-leading-relaxed tw-mb-5 tw-line-clamp-2 {{ $description ? 'tw-text-gray-600' : 'tw-text-gray-400 tw-italic' }}">
        {{ $description ?: 'Chưa có mô tả cho vai trò này.' }}
    </p>

    <div class="tw-flex tw-justify-between tw-items-center tw-mt-auto tw-border-t tw-border-gray-100 tw-pt-4">

        <span
            class="tw-bg-gray-50 tw-border tw-border-gray-200 tw-text-gray-600 tw-text-xs tw-font-medium tw-px-2.5 tw-py-1 tw-rounded-[4px] tw-flex tw-items-center tw-gap-1.5">
            <i class="fas fa-user-check tw-text-gray-400"></i> {{ $userCount }} Nhân sự
        </span>

        <a href="{{ $editUrl }}"
            class="assign-role-btn tw-text-[13px] tw-font-medium tw-text-gray-700 tw-bg-white tw-border tw-border-gray-300 hover:tw-bg-gray-50 hover:tw-text-[#0078D4] hover:tw-border-[#0078D4] tw-rounded-[4px] tw-px-4 tw-py-1.5 tw-transition-colors tw-shadow-sm tw-flex tw-items-center tw-gap-2">
            Phân quyền
        </a>
    </div>
</div>