$(function () {
    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('stock_movement.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openStockMovementModal(url) {
        ModalHelper.open('modal');
        $('#stock-movement-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#stock-movement-modal-content').html(html);
        }).fail(function (xhr) {
            $('#stock-movement-modal-content').html(loadingHtml);
            console.error('Load stock movement modal error:', xhr.status);
            console.error('Load stock movement modal error:', xhr.responseText);
        });
    }

    function handleStockMovementFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            let originalBtnText = submitBtn.html();

            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('stock_movement.save_loading') || 'Saving...'))
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
                        stockMovementTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};
                        let firstErrorMsg = Object.keys(errors).length ? Object.values(errors)[0][0] : Lang.get('stock_movement.process_failed_description');

                        fluentToast({
                            type: 'error',
                            title: Lang.get('stock_movement.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('stock_movement.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('stock_movement.system_error_description'),
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

    function attemptRestoreStockMovement(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                stockMovementTable.ajax.reload(null, false);
                fluentToast({
                    type: 'success',
                    title: Lang.get('stock_movement.undo_success_title'),
                    description: res.message || Lang.get('stock_movement.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('stock_movement.restore_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('stock_movement.restore_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                });
            },
        });
    }

    globalThis.stockMovementTable = new DataTable('#stockMovementTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[5, 'desc']],
        ajax: {
            url: route('stock-movements.data'),
            data: function (d) {
                d.stock_id = $('#f_stock').val() || '';
                d.type = $('#f_type').val() || '';
            },
        },
        columns: [
            { data: 'stock_name', name: 'stock_id', orderable: false, searchable: false },
            { data: 'type', name: 'type' },
            { data: 'quantity_changed', name: 'quantity_changed' },
            { data: 'quantity_after', name: 'quantity_after' },
            { data: 'note', name: 'note' },
            { data: 'created_at', name: 'created_at' },
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
        stockMovementTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');
        $('#f_stock, #f_type').val('').trigger('change.select2');
        stockMovementTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        stockMovementTable.ajax.reload();
    });

    $.getJSON(route('stock-movements.filter_data')).done(function (res) {
        renderOptions('#f_stock', res.stocks);
        renderOptions('#f_type', res.types);
    });

    $(document).on('click', '#delete-stock-movement-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(Lang.get('stock_movement.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                stockMovementTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('stock_movement.delete_toast_title'),
                    description: Lang.get('stock_movement.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [{ text: Lang.get('stock_movement.undo'), onClick: function () { attemptRestoreStockMovement(restoreUrl); } }],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('stock_movement.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('stock_movement.generic_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                    actionType: 'close',
                });
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    });

    $(document).on('click', '#create-stock-movement', function () {
        openStockMovementModal(route('stock-movements.create'));
    });

    handleStockMovementFormSubmit('#form-create-stock-movement');
});
