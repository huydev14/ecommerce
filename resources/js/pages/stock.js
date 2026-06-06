$(function () {
    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('stock.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openStockModal(url) {
        ModalHelper.open('modal');
        $('#stock-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#stock-modal-content').html(html);
        }).fail(function (xhr) {
            $('#stock-modal-content').html(loadingHtml);
            console.error('Load stock modal error:', xhr.status);
            console.error('Load stock modal error:', xhr.responseText);
        });
    }

    function handleStockFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            let originalBtnText = submitBtn.html();

            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('stock.save_loading') || 'Saving...'))
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
                        stockTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};
                        let firstErrorMsg = Object.keys(errors).length ? Object.values(errors)[0][0] : Lang.get('stock.process_failed_description');

                        fluentToast({
                            type: 'error',
                            title: Lang.get('stock.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('stock.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('stock.system_error_description'),
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

    function attemptRestoreStock(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                stockTable.ajax.reload(null, false);
                fluentToast({
                    type: 'success',
                    title: Lang.get('stock.undo_success_title'),
                    description: res.message || Lang.get('stock.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('stock.restore_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('stock.restore_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                });
            },
        });
    }

    globalThis.stockTable = new DataTable('#stockTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[6, 'desc']],
        ajax: {
            url: route('stocks.data'),
            data: function (d) {
                d.warehouse_id = $('#f_warehouse').val() || '';
                d.product_variant_id = $('#f_variant').val() || '';
            },
        },
        columns: [
            { data: 'variant_name', name: 'productVariant.sku', orderable: false, searchable: false },
            { data: 'warehouse_name', name: 'warehouse.name', orderable: false, searchable: false },
            { data: 'quantity', name: 'quantity' },
            { data: 'reserved_quantity', name: 'reserved_quantity' },
            { data: 'available_quantity', name: 'available_quantity', orderable: false, searchable: false },
            { data: 'low_stock_threshold', name: 'low_stock_threshold' },
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
        stockTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');
        $('#f_warehouse, #f_variant').val('').trigger('change.select2');
        stockTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        stockTable.ajax.reload();
    });

    $.getJSON(route('stocks.filter_data')).done(function (res) {
        renderOptions('#f_warehouse', res.warehouses);
        renderOptions('#f_variant', res.variants);
    });

    $(document).on('click', '#delete-stock-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(Lang.get('stock.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                stockTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('stock.delete_toast_title'),
                    description: Lang.get('stock.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [{ text: Lang.get('stock.undo'), onClick: function () { attemptRestoreStock(restoreUrl); } }],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('stock.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('stock.generic_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                    actionType: 'close',
                });
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    });

    $(document).on('click', '#create-stock', function () {
        openStockModal(route('stocks.create'));
    });

    $(document).on('click', '#edit-stock-btn', function () {
        openStockModal($(this).data('edit-url'));
    });

    handleStockFormSubmit('#form-create-stock, #form-edit-stock');
});
