$(function () {
    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('customer.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openCustomerSlideover(slideoverId, contentSelector, url) {
        openSlideover(slideoverId);
        $(contentSelector).html(loadingHtml);

        $.get(url, function (html) {
            $(contentSelector).html(html);
        }).fail(function (xhr) {
            $(contentSelector).html(loadingHtml);
            console.error('Load customer form error:', xhr.status);
            console.error('Load customer form error:', xhr.responseText);
        });
    }

    function handleCustomerFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();

            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            let originalBtnText = submitBtn.html();

            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('customer.save_loading') || 'Saving...'))
                .prop('disabled', true);

            form.find('.field-error').remove();
            form.find('.tw-border-red-500').removeClass('tw-border-red-500').addClass('tw-border-gray-300');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res, textStatus, xhr) {
                    if (res.success) {
                        let container = form.closest('.slideover-container');
                        if (container.length && typeof window.closeSlideover === 'function') {
                            window.closeSlideover(container[0]);
                        }

                        customersTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        let errors = xhr.responseJSON.errors;
                        let firstErrorMsg = Object.values(errors)[0][0];

                        $.each(errors, function (field, messages) {
                            let input = form.find(`[name="${field}"]`);
                            if (input.length) {
                                let wrapper = input.closest('.tw-flex-col');
                                input.closest('.tw-relative').removeClass('tw-border-gray-300').addClass('tw-border-red-500');
                                wrapper.append(
                                    `<span class="field-error tw-block tw-text-red-500 tw-text-xs tw-mt-1 tw-font-medium">${messages[0]}</span>`,
                                );
                            }
                        });

                        fluentToast({
                            type: 'error',
                            title: Lang.get('customer.process_failed_title'),
                            description: firstErrorMsg || Lang.get('customer.process_failed_description'),
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('customer.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('customer.system_error_description'),
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

    function attemptRestoreCustomer(restoreUrl) {
        $.ajax({
            type: 'POST',
            url: restoreUrl,
            success: function (res) {
                customersTable.ajax.reload(null, false);
                fluentToast({
                    type: 'success',
                    title: Lang.get('customer.undo_success_title'),
                    description: res.message || Lang.get('customer.undo_success_description'),
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('customer.restore_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('customer.restore_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                    actionType: 'close',
                });
            },
        });
    }

    globalThis.customersTable = new DataTable('#customers-table', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[7, 'desc']],
        ajax: {
            url: route('customers.data'),
            data: function (d) {
                d.is_active = $('#f_customerStatus').val() || '';
                d.membership_tier = $('#f_membershipTier').val() || '';
            },
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'membership_tier', name: 'membership_tier' },
            { data: 'points', name: 'points' },
            { data: 'email_verified_at', name: 'email_verified_at' },
            { data: 'is_active', name: 'is_active' },
            { data: 'updated_at', name: 'updated_at' },
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
        customersTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');
        $('#f_customerStatus, #f_membershipTier').val('').trigger('change.select2');
        customersTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        customersTable.ajax.reload();
    });

    $.getJSON(route('customers.filter_data'))
        .done(function (res) {
            renderOptions('#f_customerStatus', res.status);
            renderOptions('#f_membershipTier', res.tiers);
        })
        .fail(function (xhr) {
            console.error('Load customer filters error:', xhr.status);
            console.error('Load customer filters error:', xhr.responseText);
        });

    let preloadedCreateHtml = null;
    setTimeout(() => {
        $.get(route('customers.create'), function (html) {
            $('#content-create-customer').html(html);
            preloadedCreateHtml = html;
        });
    }, 800);

    $('#btn-open-create-customer').on('click', function () {
        openSlideover('slideover-create-customer');
        if (preloadedCreateHtml) {
            $('#content-create-customer').html(preloadedCreateHtml);
        }
    });

    $(document).on('click', '.edit-customer-btn', function () {
        openCustomerSlideover('slideover-edit-customer', '#content-edit-customer', $(this).data('edit-url'));
    });

    handleCustomerFormSubmit('#form-create-customer, #form-edit-customer');

    $(document).on('click', '.delete-customer-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm(Lang.get('customer.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                customersTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('customer.delete_toast_title'),
                    description: res.message || Lang.get('customer.delete_description'),
                    actionType: 'close',
                    bottomActions: [{ text: Lang.get('customer.undo'), onClick: function () { attemptRestoreCustomer(restoreUrl); } }],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('customer.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('customer.generic_error_description'),
                    subtitle: 'Code: ' + xhr.status,
                    actionType: 'close',
                });
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    });
});
