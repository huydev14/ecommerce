@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">

            {{-- Toolbar --}}
            <x-toolbar dataTableInstance="brandTable">
                @can('brands.create')
                    <x-create-button btnId="create-brand" />
                @endcan

            </x-toolbar>

            <div id="filter-panel" class="tw-py-3">


                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_brandName">{{ __('brand.brand') }}</x-label-small>
                        <x-filter-select id="f_brandName" :placeholder="__('brand.placeholder')" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_isActive">{{ __('brand.status') }}</x-label-small>
                        <x-filter-select id="f_isActive" :placeholder="__('brand.placeholder')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="brandTable" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('brand.name') }}</th>
                        <th>{{ __('brand.slug') }}</th>
                        <th>{{ __('brand.logo') }}</th>
                        <th>{{ __('brand.website') }}</th>
                        <th>{{ __('brand.status') }}</th>
                        <th>{{ __('brand.created_at') }}</th>
                        <th>{{ __('brand.updated_at') }}</th>
                        <th>
                            <div class="tw-text-center">{{ __('brand.action') }}</div>
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
        @vite('resources/js/pages/brand.js')
        <script>
            $(function() {
                @include('partials.fluent-session-toasts')
            })
        </script>
    @endpush
@endsection
