$(function () {

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('unit.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openUnitModal(url) {
        ModalHelper.open('modal');
        $('#unit-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#unit-modal-content').html(html);
        }).fail(function (xhr) {
            $('#unit-modal-content').html(loadingHtml);
            console.error('Load unit modal error:', xhr.status);
            console.error('Load unit modal error:', xhr.responseText);
        });
    }

    function handleUnitFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');

            let originalBtnText = submitBtn.html();
            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('unit.save_loading') || 'Saving...'))
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
                        unitTable.ajax.reload(null, false);
                        toastSuccess(res.msg, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: Lang.get('unit.process_failed_title') || 'Process failed',
                                description: Lang.get('unit.process_failed_description') || 'Invalid data. Please check your inputs.',
                                subtitle: 'Code: ' + ' ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: Lang.get('unit.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('unit.system_error_title'),
                            description: xhr.responseJSON?.msg || Lang.get('unit.system_error_description'),
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

    function attemptRestoreUnit(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                unitTable.ajax.reload(null, false);

                fluentToast({
                    type: 'success',
                    title: Lang.get('unit.undo_success_title'),
                    description: res.msg || Lang.get('unit.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('unit.restore_error_title'),
                    description: xhr.responseJSON?.msg || Lang.get('unit.restore_error_description'),
                    subtitle: 'Code: ' + ' ' + xhr.status,
                });
                console.error('Load error:', xhr.status);
                console.error('Load error:', xhr.responseText);
            },
        });
    }

    globalThis.unitTable = new DataTable('#unitTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[0, 'asc']],
        ajax: {
            url: route('units.data'),
            data: function (d) {
                d.unit_id = $('#f_unitName').val() || '';
            },
        },
        columns: [
            {
                data: 'name',
                name: 'name',
            },
            {
                data: 'short_name',
                name: 'short_name',
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
        unitTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        $('#f_unitName').val('').trigger('change.select2');
        unitTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        unitTable.ajax.reload();
    });

    $.getJSON(route('units.filter_data'))
        .done(function (res) {
            renderOptions('#f_unitName', res.units);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('click', '#delete-unit-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(Lang.get('unit.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                unitTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('unit.delete_toast_title'),
                    description: Lang.get('unit.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [
                        {
                            text: Lang.get('unit.undo'),
                            onClick: function () {
                                attemptRestoreUnit(restoreUrl);
                            },
                        },
                    ],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('unit.generic_error_title'),
                    description: xhr.responseJSON?.msg || Lang.get('unit.generic_error_description'),
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

    $(document).on('click', '#create-unit', function () {
        openUnitModal(route('units.create'));
    });

    $(document).on('click', '#edit-unit-btn', function () {
        let editUrl = $(this).data('edit-url');
        openUnitModal(editUrl);
    });

    handleUnitFormSubmit('#form-create-unit, #form-edit-unit');
});
