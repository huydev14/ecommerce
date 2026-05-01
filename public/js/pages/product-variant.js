$(function () {
    const routes = globalThis.ProductVariantRoutes;
    const i18n = globalThis.ProductVariantI18n;

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: i18n.successTitle,
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openProductVariantModal(url) {
        ModalHelper.open('modal');
        $('#product-variant-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#product-variant-modal-content').html(html);
        }).fail(function (xhr) {
            $('#product-variant-modal-content').html(loadingHtml);
            console.error('Load product variant modal error:', xhr.status);
            console.error('Load product variant modal error:', xhr.responseText);
        });
    }

    function handleProductVariantFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');

            let originalBtnText = submitBtn.html();
            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (i18n.saveLoading || 'Saving...'))
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
                        productVariantTable.ajax.reload(null, false);
                        toastSuccess(res.msg, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: i18n.processFailedTitle || 'Process failed',
                                description: i18n.processFailedDescription || 'Invalid data. Please check your inputs.',
                                subtitle: 'Code: ' + ' ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: i18n.processFailedTitle,
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: i18n.systemErrorTitle,
                            description: xhr.responseJSON?.msg || i18n.systemErrorDescription,
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

    function attemptRestoreProductVariant(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                productVariantTable.ajax.reload(null, false);

                fluentToast({
                    type: 'success',
                    title: i18n.undoSuccessTitle,
                    description: res.msg || i18n.undoSuccessDescription,
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: i18n.restoreErrorTitle,
                    description: xhr.responseJSON?.msg || i18n.restoreErrorDescription,
                    subtitle: 'Code: ' + ' ' + xhr.status,
                });
                console.error('Load error:', xhr.status);
                console.error('Load error:', xhr.responseText);
            },
        });
    }

    globalThis.productVariantTable = new DataTable('#productVariantTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[8, 'desc']],
        ajax: {
            url: routes.data,
            data: function (d) {
                d.product_id = $('#f_product').val() || '';
                d.is_active = $('#f_isActive').val() || '';
            },
        },
        columns: [
            {
                data: 'product_name',
                name: 'product.name',
                orderable: false,
                searchable: false,
            },
            {
                data: 'sku',
                name: 'sku',
            },
            {
                data: 'barcode',
                name: 'barcode',
            },
            {
                data: 'price',
                name: 'price',
            },
            {
                data: 'compare_at_price',
                name: 'compare_at_price',
            },
            {
                data: 'cost_price',
                name: 'cost_price',
            },
            {
                data: 'position',
                name: 'position',
            },
            {
                data: 'is_active',
                name: 'is_active',
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
        productVariantTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        $('#f_product, #f_isActive').val('').trigger('change.select2');
        productVariantTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        productVariantTable.ajax.reload();
    });

    $.getJSON(routes.filterData)
        .done(function (res) {
            renderOptions('#f_product', res.products);
            renderOptions('#f_isActive', res.status);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('click', '#delete-product-variant-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(i18n.confirmDelete)) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                productVariantTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: i18n.deletingTitle,
                    description: i18n.deletingDescription,
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [
                        {
                            text: i18n.undo,
                            onClick: function () {
                                attemptRestoreProductVariant(restoreUrl);
                            },
                        },
                    ],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: i18n.genericErrorTitle,
                    description: xhr.responseJSON?.msg || i18n.genericErrorDescription,
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

    $(document).on('click', '#create-product-variant', function () {
        openProductVariantModal(routes.create);
    });

    $(document).on('click', '#edit-product-variant-btn', function () {
        let editUrl = $(this).data('edit-url');
        openProductVariantModal(editUrl);
    });

    handleProductVariantFormSubmit('#form-create-product-variant, #form-edit-product-variant');
});
