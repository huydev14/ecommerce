@extends('layouts.main')

@section('page-header')
    <x-page-header title="{{ __('role.page_title') }}" description="{{ __('role.page_description') }}">
        @can('roles.create')
        <div class="tw-flex tw-items-center">
            <a href="{{ route('roles.create') }}"
                class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-[4px] tw-bg-[#0078D4] tw-px-4 tw-py-2 tw-text-[14px] tw-font-medium tw-text-white tw-shadow-sm tw-transition-colors hover:tw-bg-[#106ebe] focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0078D4] focus:tw-ring-offset-2">
                <i class="fas fa-plus tw-text-xs"></i> {{ __('role.new_role') }}
            </a>
        </div>
        @endcan
    </x-page-header>
@endsection


@section('content')
    <div class="tw-px-6 tw-pb-6">

        @if ($roles->isEmpty())
            <div class="tw-flex tw-min-h-[280px] tw-items-center tw-justify-center tw-rounded-[6px] tw-border tw-border-dashed tw-border-gray-300 tw-bg-white">
                <div class="tw-text-center">
                    <div class="tw-mx-auto tw-mb-3 tw-flex tw-h-11 tw-w-11 tw-items-center tw-justify-center tw-rounded-[6px] tw-bg-gray-100 tw-text-gray-500">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="tw-text-sm tw-font-semibold tw-text-gray-900">{{ __('role.empty_title') }}</div>
                </div>
            </div>
        @else
            <div class="tw-grid tw-grid-cols-1 tw-gap-4 lg:tw-grid-cols-3">
                @foreach ($roles as $role)
                    <article
                        class="tw-group tw-relative tw-flex tw-min-h-[218px] tw-flex-col tw-rounded-[6px] tw-border tw-border-gray-200 tw-bg-white tw-p-5 tw-shadow-sm tw-transition-all tw-duration-200 hover:tw-border-[#c7e0f4] hover:tw-shadow-md">
                        <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                            <div class="tw-flex tw-min-w-0 tw-items-center tw-gap-3">
                                <div class="tw-min-w-0">
                                    <h3 class="tw-truncate tw-text-[16px] tw-font-semibold tw-text-gray-950">{{ $role->name }}</h3>
                                </div>
                            </div>

                            @canany(['roles.edit', 'roles.remove'])
                                <div class="tw-relative role-dropdown-container">
                                    <button type="button"
                                        class="btn-role-dropdown tw-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-[4px] tw-text-gray-400 tw-transition-colors hover:tw-bg-gray-100 hover:tw-text-gray-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0078D4] focus:tw-ring-offset-1"
                                        aria-label="{{ __('role.actions_label') }}">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>

                                    <div
                                        class="role-dropdown-menu tw-hidden tw-absolute tw-right-0 tw-z-10 tw-mt-1 tw-w-44 tw-overflow-hidden tw-rounded-[4px] tw-border tw-border-gray-200 tw-bg-white tw-py-1 tw-shadow-lg">
                                        @can('roles.edit')
                                            <a href="{{ route('roles.edit', $role->id) }}"
                                                class="tw-flex tw-items-center tw-px-4 tw-py-2 tw-text-[13px] tw-text-gray-700 tw-transition-colors hover:tw-bg-gray-50">
                                                <i class="fas fa-pen tw-mr-2.5 tw-w-3 tw-text-gray-400"></i>
                                                {{ __('actions.edit_info') }}
                                            </a>
                                        @endcan
                                        @can('roles.remove')
                                            <button type="button"
                                                onclick="deleteRole({{ $role->id }}, @js($role->name), @js(route('roles.destroy', $role->id)))"
                                                class="tw-flex tw-w-full tw-items-center tw-px-4 tw-py-2 tw-text-left tw-text-[13px] tw-text-red-600 tw-transition-colors hover:tw-bg-red-50">
                                                <i class="fas fa-trash tw-mr-2.5 tw-w-3 tw-text-red-400"></i>
                                                {{ __('role.delete_role') }}
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            @endcanany
                        </div>

                        <p class="tw-line-clamp-2 tw-min-h-[40px] tw-text-[13px] tw-leading-5 {{ $role->description ? 'tw-text-gray-600' : 'tw-text-gray-400 tw-italic' }}">
                            {{ $role->description ?: __('role.no_description') }}
                        </p>

                        <div class="tw-mt-5 tw-grid tw-grid-cols-2 tw-gap-2">
                            <div class="tw-rounded-[4px] tw-bg-gray-50 tw-px-3 tw-py-2">
                                <div class="tw-text-[11px] tw-font-medium tw-text-gray-500">{{ __('user.staff') }}</div>
                                <div class="tw-mt-0.5 tw-text-[15px] tw-font-semibold tw-text-gray-900">{{ number_format($role->users_count ?? 0) }}</div>
                            </div>
                            <div class="tw-rounded-[4px] tw-bg-gray-50 tw-px-3 tw-py-2">
                                <div class="tw-text-[11px] tw-font-medium tw-text-gray-500">{{ __('role.permissions_short') }}</div>
                                <div class="tw-mt-0.5 tw-text-[15px] tw-font-semibold tw-text-gray-900">{{ number_format($role->permissions_count ?? 0) }}</div>
                            </div>
                        </div>

                        <div class="tw-mt-auto tw-flex tw-items-center tw-justify-between tw-border-t tw-border-gray-100 tw-pt-4">
                            <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded-[4px] tw-border tw-border-gray-200 tw-bg-white tw-px-2.5 tw-py-1 tw-text-xs tw-font-medium tw-text-gray-600">
                                {{ trans_choice('role.permission_count', $role->permissions_count ?? 0, ['count' => number_format($role->permissions_count ?? 0)]) }}
                            </span>

                            @can('roles.edit')
                                <a href="{{ route('roles.edit', $role->id) }}"
                                    class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-[4px] tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-1 tw-text-[13px] tw-font-medium tw-text-gray-700 tw-shadow-sm tw-transition-colors hover:tw-border-[#0078D4] hover:tw-bg-gray-50 hover:tw-text-[#0078D4]">
                                    {{ __('role.assign_permissions') }}
                                    <i class="fas fa-arrow-right tw-text-[11px]"></i>
                                </a>
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(function() {
            @include('partials.fluent-session-toasts')

            $(document).on('click', '.btn-role-dropdown', function(e) {
                e.stopPropagation();

                let $menu = $(this).siblings('.role-dropdown-menu');

                $('.role-dropdown-menu').not($menu).addClass('tw-hidden');
                $menu.toggleClass('tw-hidden');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.role-dropdown-container').length) {
                    $('.role-dropdown-menu').addClass('tw-hidden');
                }
            });

            window.deleteRole = function(id, name, deleteUrl) {
                const deleteMessage = @js(__('role.delete_confirm', ['name' => ':name'])).replace(':name', name);

                if (confirm(deleteMessage)) {
                    $.ajax({
                        type: 'DELETE',
                        url: deleteUrl,
                        success: function(res) {
                            fluentToast({
                                type: 'success',
                                title: "{{ __('actions.success') }}",
                                description: res.message,
                            });
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        },
                        error: function(xhr) {
                            fluentToast({
                                type: 'error',
                                title: "{{ __('actions.error') }}",
                                description: xhr.responseJSON?.message || "{{ __('role.delete_error') }}",
                            });
                        }
                    });
                }
            };
        });
    </script>
@endpush
