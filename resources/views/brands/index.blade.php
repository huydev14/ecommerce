@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            {{-- Toolbar --}}
            <x-toolbar dataTableInstance="brandTable">
                <x-create-button btnId="create-brand" />

            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">


                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_brandName">{{ __('brand.filter.brand') }}</x-label-small>
                        <x-filter-select id="f_brandName" :placeholder="__('brand.filter.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_isActive">{{ __('brand.filter.status') }}</x-label-small>
                        <x-filter-select id="f_isActive" :placeholder="__('brand.filter.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="brandTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('brand.table.name') }}</th>
                        <th>{{ __('brand.table.slug') }}</th>
                        <th>{{ __('brand.table.logo') }}</th>
                        <th>{{ __('brand.table.website') }}</th>
                        <th>{{ __('brand.table.status') }}</th>
                        <th>{{ __('brand.table.created_at') }}</th>
                        <th>{{ __('brand.table.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('brand.table.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="brand-modal-content"></div>
    </x-modal>

    @push('scripts')
        <script src="{{ asset('js/pages/brand.js') }}"></script>
        <script>
            window.BrandRoutes = {
                data: "{{ route('brands.data') }}",
                filterData: "{{ route('brands.filter_data') }}",
                create: "{{ route('brands.create') }}",
            };

            window.BrandI18n = {
                confirmDelete: @json(__('brand.js.confirm_delete')),
                deletingTitle: @json(__('brand.js.toast.delete_title')),
                deletingDescription: @json(__('brand.js.toast.delete_description')),
                undo: @json(__('brand.js.undo')),
                undoSuccessTitle: @json(__('brand.js.toast.undo_success_title')),
                undoSuccessDescription: @json(__('brand.js.toast.undo_success_description')),
                restoreErrorTitle: @json(__('brand.js.toast.restore_error_title')),
                restoreErrorDescription: @json(__('brand.js.toast.restore_error_description')),
                genericErrorTitle: @json(__('brand.js.toast.generic_error_title')),
                genericErrorDescription: @json(__('brand.js.toast.generic_error_description')),
                saveLoading: @json(__('brand.js.save_loading')),
                processFailedTitle: @json(__('brand.js.toast.process_failed_title')),
                processFailedDescription: @json(__('brand.js.toast.process_failed_description')),
                systemErrorTitle: @json(__('brand.js.toast.system_error_title')),
                systemErrorDescription: @json(__('brand.js.toast.system_error_description')),
                codePrefix: @json(__('brand.js.code_prefix')),
                successTitle: @json(__('brand.js.toast.success_title')),
            };

            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: @json(__('brand.js.toast.success_title')),
                        description: "{{ session('success') }}",
                        subtitle: @json(__('brand.js.code_prefix')) + ' 200',
                        actionType: 'close',
                    });
                @endif
            })
        </script>
    @endpush
@endsection
