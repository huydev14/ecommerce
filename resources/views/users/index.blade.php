@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            {{-- Toolbar --}}
            <x-toolbar dataTableInstance="usersTable">
                <x-create-button btnId="btn-open-create" target="slideover-create-user" />
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
                    <h4 class="tw-text-base tw-font-bold tw-text-gray-800">Filter</h4>
                    <button id="close-filter-btn" class="tw-text-gray-400 hover:tw-text-gray-700 tw-transition-colors">
                        <i class="fas fa-times tw-text-lg"></i>
                    </button>
                </div>

                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div>
                        <x-label id="f_department" label="Bộ phận" />
                        <x-filter-select id="f_department" />
                    </div>
                    <div>
                        <x-label id="f_employment_type" label="Hình thức" />
                        <x-filter-select id="f_employment_type" />
                    </div>
                    <div>
                        <x-label id="f_status" label="Trạng thái" />
                        <x-filter-select id="f_status" />
                    </div>
                    <div>
                        <x-label id="f_role" label="Loại tài khoản" />
                        <x-filter-select id="f_role" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="users-table" data-url="{{ route('users.data') }}" class="display table table-hover text-nowrap"
                style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Hợp đồng</th>
                        <th>Bộ phận</th>
                        <th>Số điện thoại</th>
                        <th>Ngày bắt đầu</th>
                        <th style="width: 5%">Status</th>
                        <th>
                            <div class="tw-text-center">Tác vụ</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Panel: Create user --}}
    <x-slide-over id="slideover-create-user" title="Thêm nhân viên mới">
        <div id="content-create" class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0"></div>
    </x-slide-over>

    {{-- Panel: Edit user --}}
    <x-slide-over id="slideover-edit-user" title="Cập nhật thông tin nhân viên">
        <div id="content-edit" class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0"></div>
    </x-slide-over>
@endsection

@push('scripts')
    <script>
        window.UserRoutes = {
            tableData: "{!! route('users.data') !!}",
            filterOptions: "{!! route('users.filter_data') !!}",
            teamsData: "{{ route('users.teams_data') }}",
            slideCreate: "{{ route('users.create') }}",

            showUser: "{{ route('users.show', ':id') }}"
        };
    </script>
    <script src="{{ asset('js/pages/user.js') }}" defer></script>
@endpush
