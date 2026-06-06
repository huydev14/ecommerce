@foreach ($permissionGroups as $permissionGroup)
    <div class="permission-group tw-bg-white">
        <div role="button" tabindex="0" aria-expanded="false"
            class="accordion-header tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-3 tw-border-b tw-border-gray-100 hover:tw-bg-gray-50 tw-cursor-pointer tw-transition-colors">
            <div class="tw-flex tw-items-center tw-gap-4">
                <div class="tw-w-5 tw-flex tw-justify-center tw-shrink-0">
                    <i
                        class="fas fa-chevron-right tw-text-gray-500 tw-text-sm tw-transform tw-inline-block tw-transition-transform tw-duration-200 accordion-icon"></i>
                </div>
                <div>
                    <span class="tw-font-bold tw-text-gray-900 tw-text-sm tw-block">
                        {{ $permissionGroup['title'] }}
                    </span>
                    <span class="tw-text-xs tw-text-gray-500 tw-font-normal tw-mt-0.5 tw-block">
                        {{ $permissionGroup['description'] }}
                    </span>
                </div>
            </div>

            <x-checkbox name="check_all" label="{{ __('role.check_all') }}"
                onclick="event.stopPropagation()"
                class="tw-px-3 tw-py-1 tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm hover:tw-bg-gray-50 tw-transition-colors" />
        </div>

        <div class="accordion-body tw-hidden tw-flex tw-flex-col">
            @foreach ($permissionGroup['permissions'] as $permission)
                <div>
                    <x-checkbox :label="$permission['label']" name="permissions[]" :value="$permission['name']"
                        :checked="in_array($permission['name'], $rolePermissions ?? [])"
                        class="tw-flex tw-items-center tw-cursor-pointer tw-w-full tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 hover:tw-bg-gray-50 tw-transition-colors" />
                </div>
            @endforeach
        </div>
    </div>
@endforeach
