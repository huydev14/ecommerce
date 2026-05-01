@extends('layouts.main')

@section('content')
    <div class="fluent-card ">
        <div class="card-header tw-bg-white tw-border-b-0">
            {{-- Toolbar --}}
            <x-toolbar dataTableInstance="auditLogTable" />

            <div id="filter-panel" class="tw-pt-3 tw-pb-5">


                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-x-8 tw-gap-y-4">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_logName">Phân hệ</x-label-small>
                        <x-filter-select id="f_logName" />
                    </div>
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label-small for="f_causer">Actor / Causer</x-label-small>
                        <x-filter-select id="f_causer" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body tw-pt-0">
            <table id="audit-log-table" class="display table table-hover text-nowrap" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Actor / Causer</th>
                        <th>Description</th>
                        <th>Target</th>
                        <th>Phân hệ</th>
                        <th>IP Address</th>
                        <th>Details</th>
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
                        },
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
                                    Xem chi tiết
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
                        renderOptions('#f_logName', res.logNameData);
                        renderOptions('#f_causer', res.causerData)
                    })
                    .fail(function(xhr) {
                        console.error('Load error:', xhr.status)
                        console.error('Load error:', xhr.responseText)
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
