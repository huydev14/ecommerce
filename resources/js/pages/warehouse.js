$(function () {
    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('warehouse.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openWarehouseModal(url) {
        ModalHelper.open('modal');
        $('#warehouse-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#warehouse-modal-content').html(html);
        }).fail(function (xhr) {
            $('#warehouse-modal-content').html(loadingHtml);
            console.error('Load warehouse modal error:', xhr.status);
            console.error('Load warehouse modal error:', xhr.responseText);
        });
    }

    function handleWarehouseFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            let originalBtnText = submitBtn.html();

            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('warehouse.save_loading') || 'Saving...'))
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
                        warehouseTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};
                        let firstErrorMsg = Object.keys(errors).length ? Object.values(errors)[0][0] : Lang.get('warehouse.process_failed_description');

                        fluentToast({
                            type: 'error',
                            title: Lang.get('warehouse.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('warehouse.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('warehouse.system_error_description'),
                            subtitle: 'Code: ' + xhr.status,
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

    function attemptRestoreWarehouse(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                warehouseTable.ajax.reload(null, false);
                fluentToast({
                    type: 'success',
                    title: Lang.get('warehouse.undo_success_title'),
                    description: res.message || Lang.get('warehouse.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('warehouse.restore_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('warehouse.restore_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                });
            },
        });
    }

    globalThis.warehouseTable = new DataTable('#warehouseTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[4, 'desc']],
        ajax: {
            url: route('warehouses.data'),
            data: function (d) {
                d.warehouse_id = $('#f_warehouse').val() || '';
                d.is_active = $('#f_isActive').val() || '';
            },
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'code', name: 'code' },
            { data: 'address', name: 'address' },
            { data: 'is_active', name: 'is_active' },
            { data: 'updated_at', name: 'updated_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'tw-text-center' },
        ],
        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: 'pageLength',
            bottomEnd: 'paging',
        },
    });

    $('#custom-search-input').on('keyup', function () {
        warehouseTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');
        $('#f_warehouse, #f_isActive').val('').trigger('change.select2');
        warehouseTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        warehouseTable.ajax.reload();
    });

    $.getJSON(route('warehouses.filter_data')).done(function (res) {
        renderOptions('#f_warehouse', res.warehouses);
        renderOptions('#f_isActive', res.status);
    });

    $(document).on('click', '#delete-warehouse-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(Lang.get('warehouse.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                warehouseTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('warehouse.delete_toast_title'),
                    description: Lang.get('warehouse.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [{ text: Lang.get('warehouse.undo'), onClick: function () { attemptRestoreWarehouse(restoreUrl); } }],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('warehouse.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('warehouse.generic_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                    actionType: 'close',
                });
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    });

    $(document).on('click', '#create-warehouse', function () {
        openWarehouseModal(route('warehouses.create'));
    });

    $(document).on('click', '#edit-warehouse-btn', function () {
        openWarehouseModal($(this).data('edit-url'));
    });

    handleWarehouseFormSubmit('#form-create-warehouse, #form-edit-warehouse');
});
