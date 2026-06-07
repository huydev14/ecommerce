@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            {{-- Toolbar --}}
            <x-toolbar dataTableInstance="auditLogTable" />

            <div class="tw-mt-3 tw-flex tw-gap-2 tw-border-b tw-border-gray-200">
                <button type="button"
                    class="audit-causer-tab tw-border-b-2 tw-border-blue-600 tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-blue-600"
                    data-causer-type="{{ App\Models\User::class }}">
                    {{ __('audit.user_tab') }}
                </button>
                <button type="button"
                    class="audit-causer-tab tw-border-b-2 tw-border-transparent tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-gray-500 hover:tw-text-gray-800"
                    data-causer-type="{{ App\Models\Customer::class }}">
                    {{ __('audit.customer_tab') }}
                </button>
            </div>

            <div id="filter-panel" class="tw-pt-3 tw-pb-5">


                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_logName">{{ __('audit.module') }}</x-label-small>
                        <x-filter-select id="f_logName" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_causer">{{ __('audit.actor') }}</x-label-small>
                        <x-filter-select id="f_causer" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="audit-log-table" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('audit.time') }}</th>
                        <th>{{ __('audit.actor') }}</th>
                        <th>{{ __('audit.description') }}</th>
                        <th>{{ __('audit.target') }}</th>
                        <th>{{ __('audit.module') }}</th>
                        <th>{{ __('audit.ip_address') }}</th>
                        <th>{{ __('audit.details') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <x-modal>
        <div id="audit-logs-content"></div>
    </x-modal>

    @push('scripts')
        <script type="module">
            $(function() {
                @include('partials.fluent-session-toasts')

                const causerTypes = {
                    user: @js(App\Models\User::class),
                    customer: @js(App\Models\Customer::class),
                };
                let activeCauserType = causerTypes.user;
                let filterData = {
                    logNameData: [],
                    userCauserData: [],
                    customerCauserData: [],
                };
                const initialAuditSearch = @js(request('q', ''));

                const currentCauserOptions = function() {
                    return activeCauserType === causerTypes.customer ? filterData.customerCauserData : filterData.userCauserData;
                };

                const renderCauserOptions = function() {
                    $('#f_causer').val('').trigger('change.select2');
                    renderOptions('#f_causer', currentCauserOptions());
                };

                // ---- RENDER TABLE --------------------------
                window.auditLogTable = new DataTable('#audit-log-table', {
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    ajax: {
                        url: '{!! route('audit-logs.data') !!}',
                        data: function(d) {
                            d.log_name = $('#f_logName').val() || '';
                            d.causer_id = $('#f_causer').val() || '';
                            d.causer_type = activeCauserType;
                        },
                    },
                    search: {
                        search: initialAuditSearch,
                    },
                    order: [],
                    columns: [{
                            data: 'created_at',
                            name: 'created_at',
                        },
                        {
                            data: 'causer_name',
                            name: 'causer_name',
                        },
                        {
                            data: 'description',
                            name: 'description',
                        },
                        {
                            data: 'subject_id',
                            name: 'subject_id',
                        },
                        {
                            data: 'log_name',
                            name: 'log_name',
                        },
                        {
                            data: 'ip_address',
                            name: 'ip_address',
                            orderable: false,
                        },
                        {
                            data: 'id',
                            orderable: false,
                            searchable: false,
                            render: function(data) {
                                let url = '{{ route('audit-logs.show', ':id') }}'.replace(':id', data);
                                return `
                                <button class="view-log-btn tw-text-blue-600 hover:tw-text-blue-800 tw-font-medium"
                                data-show-url="${url}">
                                    {{ __('audit.view_details') }}
                                </button>`;
                            }
                        },
                    ],
                    // createdRow: function(row, data) {
                    //     let url = '{{ route('audit-logs.show', ':id') }}'.replace(':id', data.id);

                    //     $(row).css('cursor', 'pointer').on('click', function(e) {
                    //         if ($(e.target).closest('button').length > 0) {
                    //             return;
                    //         }
                    //         window.location.href = url;
                    //     })
                    // },
                    layout: {
                        topStart: null,
                        topEnd: null,
                        bottomEnd: 'paging',
                        lengthChange: false,
                    },
                });
                $('#custom-search-input').val(initialAuditSearch);
                $('#custom-search-input').on('keyup', function() {
                    auditLogTable.search(this.value).draw();
                });

                // ---- FILTER PANEL TOGGLE ---------------------------
                $('#toggle-filter-btn').on('click', function() {
                    $('#filter-panel').slideToggle('fast');

                    // Reset filter
                    $('#f_logName, #f_causer').val('').trigger('change.select2');
                    auditLogTable.ajax.reload();
                });

                $(document).on('change', '#filter-panel select', function() {
                    auditLogTable.ajax.reload();
                });

                // ---- RENDER OPTIONS FOR SELECT FIELDs ----------------
                $.getJSON('{!! route('audit-logs.filter_data') !!}')
                    .done(function(res) {
                        filterData = res;
                        renderOptions('#f_logName', res.logNameData);
                        renderCauserOptions();
                    })
                    .fail(function(xhr) {
                        console.error('Load error:', xhr.status)
                        console.error('Load error:', xhr.responseText)
                    });

                $('.audit-causer-tab').on('click', function() {
                    activeCauserType = $(this).data('causer-type');

                    $('.audit-causer-tab')
                        .removeClass('tw-border-blue-600 tw-text-blue-600')
                        .addClass('tw-border-transparent tw-text-gray-500');

                    $(this)
                        .removeClass('tw-border-transparent tw-text-gray-500')
                        .addClass('tw-border-blue-600 tw-text-blue-600');

                    renderCauserOptions();
                    auditLogTable.ajax.reload();
                });

                $('#audit-log-table').on('click', '.view-log-btn', function() {
                    ModalHelper.open('modal')
                    $('#audit-logs-content').html(loadingHtml);

                    const showUrl = $(this).data('show-url');

                    $.get(showUrl, function(html) {
                            $('#audit-logs-content').html(html);
                        })
                        .fail(function(xhr) {
                            $('#audit-logs-content').html(loadingHtml);
                            console.error('Load audit logs content error:', xhr.status);
                            console.error('Load audit logs content error:', xhr.responseText);
                        });
                })
            });
        </script>
    @endpush
@endsection
