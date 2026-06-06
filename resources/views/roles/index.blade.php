@extends('layouts.main')

@section('page-header')
    <x-page-header title="{{ __('role.page_title') }}" description="{{ __('role.page_description') }}">
        @can('roles.create')
            <a href="{{ route('roles.create') }}"
                class="tw-bg-[#0078D4] hover:tw-bg-[#106ebe] tw-text-white tw-text-[14px] tw-font-medium tw-px-4 tw-py-2 tw-rounded-[4px] tw-shadow-sm tw-transition-colors tw-flex tw-items-center tw-gap-2">
                <i class="fas fa-plus tw-text-xs"></i> {{ __('role.new_role') }}
            </a>
        @endcan
    </x-page-header>
@endsection


@section('content')
    <div class="tw-px-6">
        {{-- Grid Layout --}}
        <div class="tw-grid tw-grid-cols-4 tw-gap-4">
            @foreach ($roles as $role)
                <div
                    class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-[8px] tw-p-5 tw-flex tw-flex-col tw-h-full hover:tw-shadow-md hover:tw-border-gray-300 tw-transition-all tw-duration-200 tw-relative tw-group">

                    {{-- Card Header --}}
                    <div class="tw-flex tw-justify-between tw-items-start tw-mb-2">
                        <div class="tw-flex tw-items-center tw-gap-2">
                            <h3 class="tw-text-[16px] tw-font-semibold tw-text-gray-900">{{ $role->name }}</h3>
                        </div>

                        @canany(['roles.edit', 'roles.remove'])
                            {{-- Action Dropdown --}}
                            <div class="tw-relative role-dropdown-container">
                                <button type="button"
                                    class="btn-role-dropdown tw-text-gray-400 hover:tw-text-gray-700 tw-w-8 tw-h-8 tw-rounded-[4px] hover:tw-bg-gray-100 tw-flex tw-items-center tw-justify-center tw-transition-colors focus:tw-outline-none">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>

                                <div
                                    class="role-dropdown-menu tw-hidden tw-absolute tw-right-0 tw-mt-1 tw-w-40 tw-bg-white tw-border tw-border-gray-200 tw-rounded-[4px] tw-shadow-lg tw-z-10 tw-py-1 tw-overflow-hidden">
                                    @can('roles.edit')
                                        <a href="{{ route('roles.edit', $role->id) }}"
                                            class="tw-flex tw-items-center tw-px-4 tw-py-2 tw-text-[13px] tw-text-gray-700 hover:tw-bg-gray-50 tw-transition-colors">
                                            <i class="fas fa-pen tw-mr-2.5 tw-text-gray-400 tw-w-3"></i>
                                            {{ __('actions.edit_info') }}
                                        </a>
                                    @endcan
                                    @can('roles.remove')
                                        <button type="button"
                                            onclick="deleteRole({{ $role->id }}, '{{ $role->name }}', '{{ route('roles.destroy', $role->id) }}')"
                                            class="tw-w-full tw-flex tw-items-center tw-px-4 tw-py-2 tw-text-[13px] tw-text-red-600 hover:tw-bg-red-50 tw-transition-colors">
                                            <i class="fas fa-trash tw-mr-2.5 tw-text-red-400 tw-w-3"></i>
                                            {{ __('role.delete_role') }}
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @endcanany
                    </div>

                    {{-- Card Body: Description --}}
                    <p
                        class="tw-text-[13px] tw-flex-grow tw-leading-relaxed tw-mb-5 tw-line-clamp-2 {{ $role->description ? 'tw-text-gray-600' : 'tw-text-gray-400 tw-italic' }}">
                        {{ $role->description ?: __('role.no_description') }}
                    </p>

                    {{-- Card Footer --}}
                    <div
                        class="tw-flex tw-justify-between tw-items-center tw-mt-auto tw-border-t tw-border-gray-100 tw-pt-4">
                        <span
                            class="tw-bg-gray-50 tw-border tw-border-gray-200 tw-text-gray-600 tw-text-xs tw-font-medium tw-px-2.5 tw-py-1 tw-rounded-[4px] tw-flex tw-items-center tw-gap-1.5">
                            <i class="fas fa-user-check tw-text-gray-400"></i> {{ $role->users_count ?? 0 }}
                            {{ __('user.staff') }}
                        </span>

                        @can('roles.edit')
                            <a href="{{ route('roles.edit', $role->id) }}"
                                class="assign-role-btn tw-text-[13px] tw-font-medium tw-text-gray-700 tw-bg-white tw-border tw-border-gray-300 hover:tw-bg-gray-50 hover:tw-text-[#0078D4] hover:tw-border-[#0078D4] tw-rounded-[4px] tw-px-4 tw-py-1.5 tw-transition-colors tw-shadow-sm tw-flex tw-items-center tw-gap-2">
                                {{ __('role.assign_permissions') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(function() {
            @if (session('success'))
                fluentToast({
                    type: 'success',
                    title: "{{ __('Success') }}",
                    description: "{{ session('success') }}",
                    subtitle: 'Code: 200',
                    actionType: 'close',
                });
            @endif

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
                if (confirm("{{ __('role.delete_confirm', ['name' => '']) }}" + name)) {
                    $.ajax({
                        type: 'DELETE',
                        url: deleteUrl,
                        success: function(res) {
                            fluentToast({
                                type: 'success',
                                title: "{{ __('actions.success') }}",
                                description: res.msg,
                            });
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        },
                        error: function(xhr) {
                            fluentToast({
                                type: 'error',
                                title: "{{ __('actions.error') }}",
                                description: xhr.responseJSON?.msg ||
                                    "{{ __('role.delete_error') }}",
                            });
                        }
                    });
                }
            };
        });
    </script>
@endpush
