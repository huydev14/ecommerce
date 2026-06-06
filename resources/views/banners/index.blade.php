@extends('layouts.main')

@section('content')
    <div class="fluent-card">
        <div class="card-header tw-bg-white tw-border-b-0">
            <x-toolbar dataTableInstance="bannerTable">
                @can('banners.create')
                    <x-create-button btnId="create-banner" />
                @endcan
            </x-toolbar>
        </div>

        <div class="card-body tw-pt-0">
            <div class="table-responsive">
                <table id="bannerTable" class="display table table-hover text-nowrap" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Ảnh</th>
                            <th>Liên kết</th>
                            <th>Thứ tự</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Cập nhật</th>
                            <th>
                                <div class="tw-text-center">Tác vụ</div>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <x-modal>
        <div id="banner-modal-content"></div>
    </x-modal>

    @push('scripts')
        @vite('resources/js/pages/banner.js')
        <script>
            $(function() {
                @include('partials.fluent-session-toasts')
            })
        </script>
    @endpush
@endsection
