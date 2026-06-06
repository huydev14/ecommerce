$(function () {

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('tax.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openTaxModal(url) {
        ModalHelper.open('modal');
        $('#tax-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#tax-modal-content').html(html);
        }).fail(function (xhr) {
            $('#tax-modal-content').html(loadingHtml);
            console.error('Load tax modal error:', xhr.status);
            console.error('Load tax modal error:', xhr.responseText);
        });
    }

    function handleTaxFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');

            let originalBtnText = submitBtn.html();
            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('tax.save_loading') || 'Saving...'))
                .prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res, textStatus, xhr) {
                    if (res.success) {
                        ModalHelper.close('modal');
                        taxTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: Lang.get('tax.process_failed_title') || 'Process failed',
                                description: Lang.get('tax.process_failed_description') || 'Invalid data. Please check your inputs.',
                                subtitle: 'Code: ' + ' ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: Lang.get('tax.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('tax.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('tax.system_error_description'),
                            subtitle: 'Code: ' + ' ' + xhr.status,
                            actionType: 'close',
                        });
                    }
                },
                complete: function () {
                    submitBtn.html(originalBtnText).prop('disabled', false);
                },
            });
        });
    }

    function attemptRestoreTax(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                taxTable.ajax.reload(null, false);

                fluentToast({
                    type: 'success',
                    title: Lang.get('tax.undo_success_title'),
                    description: res.message || Lang.get('tax.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('tax.restore_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('tax.restore_error_description'),
                    subtitle: 'Code: ' + ' ' + xhr.status,
                });
                console.error('Load error:', xhr.status);
                console.error('Load error:', xhr.responseText);
            },
        });
    }

    globalThis.taxTable = new DataTable('#taxTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[0, 'asc']],
        ajax: {
            url: route('taxes.data'),
            data: function (d) {
                d.tax_id = $('#f_taxName').val() || '';
            },
        },
        columns: [
            {
                data: 'name',
                name: 'name',
            },
            {
                data: 'rate',
                name: 'rate',
            },
            {
                data: 'created_at',
                name: 'created_at',
            },
            {
                data: 'updated_at',
                name: 'updated_at',
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'tw-text-center',
            },
        ],

        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: 'pageLength',
            bottomEnd: 'paging',
        },
    });

    $('#custom-search-input').on('keyup', function () {
        taxTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        $('#f_taxName').val('').trigger('change.select2');
        taxTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        taxTable.ajax.reload();
    });

    $.getJSON(route('taxes.filter_data'))
        .done(function (res) {
            renderOptions('#f_taxName', res.taxes);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('click', '#delete-tax-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(Lang.get('tax.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                taxTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('tax.delete_toast_title'),
                    description: Lang.get('tax.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [
                        {
                            text: Lang.get('tax.undo'),
                            onClick: function () {
                                attemptRestoreTax(restoreUrl);
                            },
                        },
                    ],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('tax.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('tax.generic_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                    actionType: 'close',
                });
                console.error('Load error:', xhr.status);
                console.error('Load error:', xhr.responseText);
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    });

    $(document).on('click', '#create-tax', function () {
        openTaxModal(route('taxes.create'));
    });

    $(document).on('click', '#edit-tax-btn', function () {
        let editUrl = $(this).data('edit-url');
        openTaxModal(editUrl);
    });

    handleTaxFormSubmit('#form-create-tax, #form-edit-tax');
});
