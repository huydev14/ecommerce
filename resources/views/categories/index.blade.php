@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            <x-toolbar dataTableInstance="categoryTable">
                @can('categories.create')
                    <x-create-button btnId="create-category" />
                @endcan
            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">

                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_categoryName">{{ __('category.category') }}</x-label-small>
                        <x-filter-select id="f_categoryName" :placeholder="__('category.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_isActive">{{ __('category.status') }}</x-label-small>
                        <x-filter-select id="f_isActive" :placeholder="__('category.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="categoryTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('category.name') }}</th>
                        <th>{{ __('category.slug') }}</th>
                        <th>{{ __('category.parent') }}</th>
                        <th>{{ __('category.description') }}</th>
                        <th>{{ __('category.status') }}</th>
                        <th>{{ __('category.created_at') }}</th>
                        <th>{{ __('category.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('category.action') }}</div>
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
        @vite('resources/js/pages/category.js')
        <script>
            $(function() {
                @include('partials.fluent-session-toasts')
            });
        </script>
    @endpush
@endsection
