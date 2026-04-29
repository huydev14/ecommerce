@extends('layouts.main')

@section('page-header')
    <x-page-header title="Roles & Permissions" description="Review your members roles and allocate permissions">
        <x-slot:action>
            <a href="{{ route('roles.create') }}"
                class="tw-bg-[#0078D4] hover:tw-bg-[#106ebe] tw-text-white tw-text-[14px] tw-font-medium tw-px-4 tw-py-2 tw-rounded-[4px] tw-shadow-sm tw-transition-colors tw-flex tw-items-center tw-gap-2">
                <i class="fas fa-plus tw-text-xs"></i> New Role
            </a>
        </x-slot:action>
    </x-page-header>
@endsection

@section('content')
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-3">
        @foreach ($roles as $role)
            <x-role-card title="{{ $role->name }}" description="{{ $role->description }}"
                userCount="{{ $role->users_count ?? 0 }}" roleId="{{ $role->id }}"
                editUrl="{{ route('roles.edit', $role->id) }}" />
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            @if (session('success'))
                fluentToast({
                    type: 'success',
                    title: 'Thành công',
                    description: "{{ session('success') }}",
                    subtitle: 'Code: 200',
                    actionType: 'close',
                });
            @endif

            $(document).on('click', '.btn-role-dropdown', function (e) {
                e.stopPropagation();

                let $menu = $(this).siblings('.role-dropdown-menu');

                $('.role-dropdown-menu').not($menu).addClass('tw-hidden');
                $menu.toggleClass('tw-hidden');
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('.role-dropdown-container').length) {
                    $('.role-dropdown-menu').addClass('tw-hidden');
                }
            });

            $(document).on('click', '#delete-role-btn', function () {
                if (confirm(`Bạn có chắc muốn xóa vai trò này không?`)) {
                    let $btn = $(this);
                    let deleteUrl = $btn.data('delete-url');
                    let restoreUrl = $btn.data('restore-url');

                    $btn.prop('disabled', true);
                    $.ajax({
                        type: 'DELETE',
                        url: deleteUrl,
                        success: function (res) {
                            window.location.reload();
                        },
                        error: function (xhr) {
                            console.error('Load error:', xhr.status)
                            console.error('Load error:', xhr.responseText)
                        },
                        complete: function () {
                            $btn.prop('disabled', false);
                        }
                    })
                }
            })
        });
    </script>
@endpush
