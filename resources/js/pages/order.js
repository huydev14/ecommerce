$(function () {
    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('order.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openOrderModal(url) {
        ModalHelper.open('modal');
        $('#order-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#order-modal-content').html(html);
        }).fail(function (xhr) {
            $('#order-modal-content').html(loadingHtml);
            console.error('Load order modal error:', xhr.status);
            console.error('Load order modal error:', xhr.responseText);
        });
    }

    function handleOrderFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            let originalBtnText = submitBtn.html();

            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('order.save_loading') || 'Saving...'))
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
                        orderTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};
                        let firstErrorMsg = Object.keys(errors).length ? Object.values(errors)[0][0] : Lang.get('order.process_failed_description');

                        fluentToast({
                            type: 'error',
                            title: Lang.get('order.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('order.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('order.system_error_description'),
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

    globalThis.orderTable = new DataTable('#orderTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 6,
        ajax: {
            url: route('orders.data'),
            data: function (d) {
                d.status = $('#f_orderStatus').val() || '';
                d.payment_status = $('#f_paymentStatus').val() || '';
                d.payment_method = $('#f_paymentMethod').val() || '';
            },
        },
        columns: [
            {
                data: 'order_number',
                name: 'order_number',
                render: function (data, type, row) {
                    return (
                        '<div class="tw-flex tw-flex-col tw-items-start tw-gap-1">' +
                        row.status +
                        data +
                        '<span class="tw-text-xs tw-text-gray-400">' + row.created_at + '</span>' +
                        '</div>'
                    );
                },
            },
            { data: 'customer', name: 'customer_name', orderable: false },
            { data: 'order_items', name: 'items_count', orderable: false, searchable: false },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'tw-text-center' },
        ],
        layout: {
            topStart: null,
            topEnd: null,
            bottomEnd: 'paging',
        },
    });

    $('#custom-search-input').on('keyup', function () {
        orderTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');
        $('#f_orderStatus, #f_paymentStatus, #f_paymentMethod').val('').trigger('change.select2');
        orderTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        orderTable.ajax.reload();
    });

    $.getJSON(route('orders.filter_data')).done(function (res) {
        renderOptions('#f_orderStatus', res.statuses);
        renderOptions('#f_paymentStatus', res.payment_statuses);
        renderOptions('#f_paymentMethod', res.payment_methods);
    });

    $(document).on('click', '#show-order-btn, .show-order-status', function () {
        openOrderModal($(this).data('show-url'));
    });

    handleOrderFormSubmit('#form-update-order');
});
