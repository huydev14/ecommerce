@extends('layouts.main')

@section('content')
    <div class="fluent-card !tw-pb-0">
        <div class="card-header tw-bg-white tw-border-b-0">

            {{-- Toolbar --}}
            <x-toolbar dataTableInstance="usersTable">
                @can('users.create')
                    <x-create-button btnId="btn-open-create" target="slideover-create-user" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">

                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_department">Bộ phận</x-label-small>
                        <x-filter-select id="f_department" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_employment_type">Hình thức</x-label-small>
                        <x-filter-select id="f_employment_type" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_status">Trạng thái</x-label-small>
                        <x-filter-select id="f_status" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_role">Loại tài khoản</x-label-small>
                        <x-filter-select id="f_role" />
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive table-scroll-container card-body tw-pt-0">
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
    @vite('resources/js/pages/user.js')
    <script type="module">
        $(function() {
            @include('partials.fluent-session-toasts')
        });
    </script>
@endpush
