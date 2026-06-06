$(function () {

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('brand.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openBrandModal(url) {
        ModalHelper.open('modal');
        $('#brand-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#brand-modal-content').html(html);
        }).fail(function (xhr) {
            $('#brand-modal-content').html(loadingHtml);
            console.error('Load brand modal error:', xhr.status);
            console.error('Load brand modal error:', xhr.responseText);
        });
    }

    function handleBrandFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');

            let originalBtnText = submitBtn.html();
            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('brand.save_loading') || 'Saving...'))
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
                        brandTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: Lang.get('brand.process_failed_title') || 'Process failed',
                                description: Lang.get('brand.process_failed_description') || 'Invalid data. Please check your inputs.',
                                subtitle: 'Code: ' + ' ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: Lang.get('brand.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('brand.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('brand.system_error_description'),
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

    // ---- RENDER TABLE --------------------------
    globalThis.brandTable = new DataTable('#brandTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[3, 'desc']],
        ajax: {
            url: route('brands.data'),
            data: function (d) {
                d.status = $('#f_brandName').val() || '';
                d.department_id = $('#f_isActive').val() || '';
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
                data: 'logo',
                name: 'logo',
            },
            {
                data: 'website',
                name: 'website',
            },
            {
                data: 'is_active',
                name: 'is_active',
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
        createdRow: function (row, data) {
            let url = route('brands.show', data.id);

            $(row)
                .css('cursor', 'pointer')
                .on('click', function (e) {
                    if ($(e.target).closest('button').length > 0) {
                        return;
                    }
                    globalThis.location.href = url;
                });
        },

        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: 'pageLength',
            bottomEnd: 'paging',
        },
    });
    $('#custom-search-input').on('keyup', function () {
        brandTable.search(this.value).draw();
    });

    // ---- FILTER PANEL TOGGLE ---------------------------
    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        // Reset filter
        $('#f_brandName, #f_isActive').val('').trigger('change.select2');
        brandTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        brandTable.ajax.reload();
    });

    // ---- RENDER OPTIONS FOR SELECT FIELDs ----------------
    $.getJSON(route('brands.filter_data'))
        .done(function (res) {
            renderOptions('#f_brandName', res.brandName);
            renderOptions('#f_isActive', res.isActive);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    // ---- Delete brand ------------------------
    $(document).on('click', '#delete-brand-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');

        if (!confirm(Lang.get('brand.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                brandTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('brand.delete_toast_title'),
                    description: Lang.get('brand.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('brand.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('brand.generic_error_description'),
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

    $(document).on('click', '#create-brand', function () {
        openBrandModal(route('brands.create'));
    });

    $(document).on('click', '#edit-brand-btn', function () {
        let editUrl = $(this).data('edit-url');
        openBrandModal(editUrl);
    });

    handleBrandFormSubmit('#form-create-brand, #form-edit-brand');
});
