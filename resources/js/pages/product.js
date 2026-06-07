$(function () {

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('product.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openProductModal(url) {
        ModalHelper.open('modal');
        $('#product-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#product-modal-content').html(html);
        }).fail(function (xhr) {
            $('#product-modal-content').html(loadingHtml);
            console.error('Load product modal error:', xhr.status);
            console.error('Load product modal error:', xhr.responseText);
        });
    }

    function handleProductFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');

            let originalBtnText = submitBtn.html();
            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('product.save_loading') || 'Saving...'))
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
                        productTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: Lang.get('product.process_failed_title') || 'Process failed',
                                description: Lang.get('product.process_failed_description') || 'Invalid data. Please check your inputs.',
                                subtitle: 'Code: ' + ' ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: Lang.get('product.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('product.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('product.system_error_description'),
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

    function attemptRestoreProduct(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                productTable.ajax.reload(null, false);

                fluentToast({
                    type: 'success',
                    title: Lang.get('product.undo_success_title'),
                    description: res.message || Lang.get('product.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('product.restore_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('product.restore_error_description'),
                    subtitle: 'Code: ' + ' ' + xhr.status,
                });
                console.error('Load error:', xhr.status);
                console.error('Load error:', xhr.responseText);
            },
        });
    }

    globalThis.productTable = new DataTable('#productTable', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: route('products.data'),
            data: function (d) {
                d.product_id = $('#f_productName').val() || '';
                d.category_id = $('#f_category').val() || '';
                d.brand_id = $('#f_brand').val() || '';
                d.status = $('#f_status').val() || '';
                d.is_featured = $('#f_isFeatured').val() || '';
            },
        },

        columns: [
            {
                data: 'name',
                name: 'name',
                className: 'tw-max-w-[300px] tw-truncate tw-whitespace-nowrap',
            },
            {
                data: 'slug',
                name: 'slug',
                className: 'tw-max-w-[300px] tw-truncate tw-whitespace-nowrap',
            },
            {
                data: 'category_name',
                name: 'category.name',
                orderable: false,
                searchable: false,
            },
            {
                data: 'brand_name',
                name: 'brand.name',
                orderable: false,
                searchable: false,
                className: 'tw-max-w-[130px] tw-truncate tw-whitespace-nowrap',
            },
            {
                data: 'status',
                name: 'status',
            },
            {
                data: 'is_featured',
                name: 'is_featured',
                className: 'tw-max-w-[80px] tw-truncate tw-whitespace-nowrap',
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
        productTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        $('#f_productName, #f_category, #f_brand, #f_status, #f_isFeatured').val('').trigger('change.select2');
        productTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        productTable.ajax.reload();
    });

    $.getJSON(route('products.filter_data'))
        .done(function (res) {
            renderOptions('#f_productName', res.products);
            renderOptions('#f_category', res.categories);
            renderOptions('#f_brand', res.brands);
            renderOptions('#f_status', res.status);
            renderOptions('#f_isFeatured', res.featured_statuses);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('click', '#delete-product-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(Lang.get('product.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                productTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('product.delete_toast_title'),
                    description: Lang.get('product.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [
                        {
                            text: Lang.get('product.undo'),
                            onClick: function () {
                                attemptRestoreProduct(restoreUrl);
                            },
                        },
                    ],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('product.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('product.generic_error_description'),
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

    $(document).on('click', '#create-product', function () {
        openProductModal(route('products.create'));
    });

    $(document).on('click', '#edit-product-btn', function () {
        let editUrl = $(this).data('edit-url');
        openProductModal(editUrl);
    });

    handleProductFormSubmit('#form-create-product, #form-edit-product');
});
