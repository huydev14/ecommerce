@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            <x-toolbar dataTableInstance="categoryTable">
                <x-create-button btnId="create-category" />
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
                    <h4 class="tw-text-base tw-font-bold tw-text-gray-800">{{ __('category.filter.title') }}</h4>
                    <button id="close-filter-btn" class="tw-text-gray-400 hover:tw-text-gray-700 tw-transition-colors">
                        <i class="fas fa-times tw-text-lg"></i>
                    </button>
                </div>

                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div>
                        <x-label id="f_categoryName" :label="__('category.filter.category')" />
                        <x-filter-select id="f_categoryName" :placeholder="__('category.filter.placeholder')" />
                    </div>
                    <div>
                        <x-label id="f_isActive" :label="__('category.filter.status')" />
                        <x-filter-select id="f_isActive" :placeholder="__('category.filter.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="categoryTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('category.table.name') }}</th>
                        <th>{{ __('category.table.slug') }}</th>
                        <th>{{ __('category.table.parent') }}</th>
                        <th>{{ __('category.table.description') }}</th>
                        <th>{{ __('category.table.status') }}</th>
                        <th>{{ __('category.table.created_at') }}</th>
                        <th>{{ __('category.table.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('category.table.action') }}</div>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="category-modal-content"></div>
    </x-modal>

    @push('scripts')
        <script src="{{ asset('js/pages/category.js') }}"></script>
        <script>
            window.CategoryRoutes = {
                data: "{{ route('categories.data') }}",
                filterData: "{{ route('categories.filter_data') }}",
                create: "{{ route('categories.create') }}",
            };

            window.CategoryI18n = {
                confirmDelete: @json(__('category.js.confirm_delete')),
                deletingTitle: @json(__('category.js.toast.delete_title')),
                deletingDescription: @json(__('category.js.toast.delete_description')),
                undo: @json(__('category.js.undo')),
                undoSuccessTitle: @json(__('category.js.toast.undo_success_title')),
                undoSuccessDescription: @json(__('category.js.toast.undo_success_description')),
                restoreErrorTitle: @json(__('category.js.toast.restore_error_title')),
                restoreErrorDescription: @json(__('category.js.toast.restore_error_description')),
                genericErrorTitle: @json(__('category.js.toast.generic_error_title')),
                genericErrorDescription: @json(__('category.js.toast.generic_error_description')),
                saveLoading: @json(__('category.js.save_loading')),
                processFailedTitle: @json(__('category.js.toast.process_failed_title')),
                processFailedDescription: @json(__('category.js.toast.process_failed_description')),
                systemErrorTitle: @json(__('category.js.toast.system_error_title')),
                systemErrorDescription: @json(__('category.js.toast.system_error_description')),
                codePrefix: @json(__('category.js.code_prefix')),
                successTitle: @json(__('category.js.toast.success_title')),
            };

            $(function() {
                @if (session('success'))
                    fluentToast({
                        type: 'success',
                        title: @json(__('category.js.toast.success_title')),
                        description: "{{ session('success') }}",
                        subtitle: @json(__('category.js.code_prefix')) + ' 200',
                        actionType: 'close',
                    });
                @endif
            });
        </script>
    @endpush
@endsection
