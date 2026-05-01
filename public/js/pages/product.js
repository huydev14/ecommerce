$(function () {
    const routes = globalThis.ProductRoutes;
    const i18n = globalThis.ProductI18n;

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: i18n.successTitle,
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
                        productTable.ajax.reload(null, false);
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

    function attemptRestoreProduct(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                productTable.ajax.reload(null, false);

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

    globalThis.productTable = new DataTable('#productTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[0, 'asc']],
        ajax: {
            url: routes.data,
            data: function (d) {
                d.product_id = $('#f_productName').val() || '';
                d.category_id = $('#f_category').val() || '';
                d.brand_id = $('#f_brand').val() || '';
                d.status = $('#f_status').val() || '';
            },
        },
        columns: [
            {
                data: 'name',
                name: 'name',
            },
            {
                data: 'slug',
                name: 'slug',
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
            },
            {
                data: 'status',
                name: 'status',
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
        productTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        $('#f_productName, #f_category, #f_brand, #f_status').val('').trigger('change.select2');
        productTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        productTable.ajax.reload();
    });

    $.getJSON(routes.filterData)
        .done(function (res) {
            renderOptions('#f_productName', res.products);
            renderOptions('#f_category', res.categories);
            renderOptions('#f_brand', res.brands);
            renderOptions('#f_status', res.status);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('click', '#delete-product-btn', function () {
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
                productTable.ajax.reload(null, false);
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
                                attemptRestoreProduct(restoreUrl);
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

    $(document).on('click', '#create-product', function () {
        openProductModal(routes.create);
    });

    $(document).on('click', '#edit-product-btn', function () {
        let editUrl = $(this).data('edit-url');
        openProductModal(editUrl);
    });

    handleProductFormSubmit('#form-create-product, #form-edit-product');
});
