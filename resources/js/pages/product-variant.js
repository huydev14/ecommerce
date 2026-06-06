$(function () {

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('product_variant.success_title'),
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
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('product_variant.save_loading') || 'Saving...'))
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
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: Lang.get('product_variant.process_failed_title') || 'Process failed',
                                description: Lang.get('product_variant.process_failed_description') || 'Invalid data. Please check your inputs.',
                                subtitle: 'Code: ' + ' ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: Lang.get('product_variant.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('product_variant.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('product_variant.system_error_description'),
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
                    title: Lang.get('product_variant.undo_success_title'),
                    description: res.message || Lang.get('product_variant.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('product_variant.restore_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('product_variant.restore_error_description'),
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
        order: [[6, 'desc']],
        ajax: {
            url: route('product-variants.data'),
            data: function (d) {
                d.is_active = $('#f_isActive').val() || '';
            },
        },
        columns: [
            {
                data: 'variant_name',
                name: 'variant_name',
                orderable: false,
                searchable: false,
            },
            {
                data: 'sku',
                name: 'sku',
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

        $('#f_isActive').val('').trigger('change.select2');
        productVariantTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        productVariantTable.ajax.reload();
    });

    $.getJSON(route('product-variants.filter_data'))
        .done(function (res) {
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

        if (!confirm(Lang.get('product_variant.confirm_delete'))) {
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
                    title: Lang.get('product_variant.delete_toast_title'),
                    description: Lang.get('product_variant.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [
                        {
                            text: Lang.get('product_variant.undo'),
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
                    title: Lang.get('product_variant.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('product_variant.generic_error_description'),
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
        openProductVariantModal(route('product-variants.create'));
    });

    $(document).on('click', '#edit-product-variant-btn', function () {
        let editUrl = $(this).data('edit-url');
        openProductVariantModal(editUrl);
    });

    handleProductVariantFormSubmit('#form-create-product-variant, #form-edit-product-variant');
});
