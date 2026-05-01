let ajaxUserFormRequest = function (formSelector, dataTableInstance = null, reloadPage = false) {
    $(document).on('submit', formSelector, function (e) {
        e.preventDefault();

        let form = $(this);
        let url = form.attr('action');
        let formData = new FormData(this);
        let submitBtn = form.find('button[type="submit"]');
        let originalBtnText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> Đang xử lý...');

        form.find('.field-error').remove();
        form.find('.tw-border-red-500').removeClass('tw-border-red-500').addClass('tw-border-gray-300');

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            success: function (res, textStatus, xhr) {
                submitBtn.prop('disabled', false).html(originalBtnText);

                if (res.success) {
                    let container = form.closest('.slideover-container');
                    if (container.length && typeof window.closeSlideover === 'function') {
                        window.closeSlideover(container[0]);
                    }

                    if (dataTableInstance) {
                        dataTableInstance.ajax.reload(null, false);
                        form[0].reset();
                    }

                    if (reloadPage) {
                        setTimeout(() => window.location.reload(), 1000);
                    }

                    fluentToast({
                        type: 'success',
                        title: 'Thành công',
                        description: res.msg || 'Dữ liệu đã được lưu vào hệ thống',
                        subtitle: 'Code: ' + xhr.status,
                        actionType: 'close',
                    });
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html(originalBtnText);

                // Validate failed
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
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
                        title: 'Xử lý thất bại',
                        description: 'Hãy kiểm tra lại các trường thông tin.',
                        subtitle: 'Mã lỗi: ' + xhr.status,
                        actionType: 'close',
                    });
                } else {
                    fluentToast({
                        type: 'error',
                        title: 'Lỗi hệ thống',
                        description: xhr.responseJSON?.msg || 'Đã có lỗi xảy ra, vui lòng thử lại!',
                        subtitle: 'Mã lỗi: ' + xhr.status,
                        actionType: 'close',
                    });
                }
            },
        });
    });
};

$(function () {
    // ---- RENDER TABLE --------------------------
    let usersTable = new DataTable('#users-table', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[0, 'desc']],
        lengthChange: false,
        paging: false,
        ajax: {
            url: globalThis.UserRoutes.tableData,
            data: function (d) {
                d.status = $('#f_status').val() || '';
                d.department_id = $('#f_department').val() || '';
                d.employment_type_id = $('#f_employment_type').val() || '';
                d.role = $('#f_role').val() || '';
            },
        },
        columns: [
            {
                data: 'id',
                name: 'id',
            },
            {
                data: 'name',
                name: 'name',
            },
            {
                data: 'email',
                name: 'email',
            },
            {
                data: 'role',
                name: 'role',
            },
            {
                data: 'employment_type',
                name: 'employment_type',
            },
            {
                data: 'department.name',
                name: 'department.name',
            },
            {
                data: 'phone',
                name: 'phone',
            },
            {
                data: 'start_date',
                name: 'start_date',
            },
            {
                data: 'status',
                name: 'status',
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
            let url = globalThis.UserRoutes.showUser.replace(':id', data.id);

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
        usersTable.search(this.value).draw();
    });

    // ---- FILTER PANEL TOGGLE ---------------------------
    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        // Reset filter
        $('#f_status, #f_department, #f_employment_type, #f_role').val('').trigger('change.select2');
        usersTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        usersTable.ajax.reload();
    });

    // ---- RENDER OPTIONS FOR SELECT FIELDs ----------------
    $.getJSON(globalThis.UserRoutes.filterOptions)
        .done(function (res) {
            renderOptions('#f_department', res.department_data);
            renderOptions('#f_status', res.status_data);
            renderOptions('#f_employment_type', res.employment_type_data);
            renderOptions('#f_role', res.role_data);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('change', '#create-department, #edit-department', function () {
        let departmentId = $(this).val();
        let isCreateForm = $(this).attr('id') === 'create-department';
        let targetTeamSelector = isCreateForm ? '#create-team' : '#edit-team';

        $.getJSON(globalThis.UserRoutes.teams_data, {
            department_id: departmentId,
        }).done(function (res) {
            renderOptions(targetTeamSelector, res.teams_data);
        });
    });

    // --- Open create user slide-over -------------------------
    let preloadedCreateHtml = null;
    setTimeout(() => {
        $.get(window.UserRoutes.slideCreate, function (html) {
            $('#content-create').html(html);
            preloadedCreateHtml = html;
        });
    }, 800);

    $('#btn-open-create').on('click', function () {
        openSlideover('slideover-create-user');
        if (preloadedCreateHtml) {
            $('#content-create').html(preloadedCreateHtml);
        }
    });

    $(document).on('click', '.edit-user-btn, #edit-user-btn', function () {
        let editUrl = $(this).data('edit-url');
        openSlideover('slideover-edit-user');
        $('#content-edit').html(loadingHtml);

        $.get(editUrl, function (html) {
            $('#content-edit').html(html);
        }).fail(function (xhr) {
            $('#content-edit').html(loadingHtml);
            console.error('Load edit form error:', xhr.status);
            console.error('Load edit form error:', xhr.responseText);
        });
    });

    // --- Handle create/edit user ---------------------------
    ajaxUserFormRequest('#form-create-user, #form-edit-user', usersTable);

    // ---- Delete user ------------------------
    $(document).on('click', '#delete-user-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');
        let restoreUrl = $btn.data('restore-url');

        if (!confirm('Confirm delete?')) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                usersTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: 'Đã xóa nhân viên',
                    description: 'Tài khoản nhân viên đã được chuyển vào thùng rác.',
                    subtitle: res.status,
                    actionType: 'close',
                    bottomActions: [
                        {
                            text: 'Hoàn tác',

                            // Restore soft-deleted user
                            onClick: function () {
                                $.ajax({
                                    type: 'POST',
                                    url: restoreUrl,
                                    success: function (res) {
                                        usersTable.ajax.reload(null, false);

                                        fluentToast({
                                            type: 'success',
                                            title: 'Hoàn tác thành công',
                                            description: 'Tài khoản nhân viên đã được khôi phục hoạt động.',
                                            actionType: 'close',
                                        });
                                    },
                                    error: function (xhr) {
                                        fluentToast({
                                            type: 'error',
                                            title: 'Lỗi khôi phục',
                                            description: 'Không thể hoàn tác thao tác này.',
                                            subtitle: 'Mã lỗi: ' + xhr.status,
                                        });
                                        console.error('Load error:', xhr.status);
                                        console.error('Load error:', xhr.responseText);
                                    },
                                });
                            },
                        },
                    ],
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: 'Đã xảy ra lỗi!',
                    description: 'Hãy thử lại sau',
                    subtitle: 'Mã lỗi: ' + xhr.status,
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
});
