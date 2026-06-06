$(function () {

    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('category.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openCategoryModal(url) {
        ModalHelper.open('modal');
        $('#category-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#category-modal-content').html(html);
        }).fail(function (xhr) {
            $('#category-modal-content').html(loadingHtml);
            console.error('Load category modal error:', xhr.status);
            console.error('Load category modal error:', xhr.responseText);
        });
    }

    function handleCategoryFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');

            let originalBtnText = submitBtn.html();
            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('category.save_loading') || 'Saving...'))
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
                        categoryTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: Lang.get('category.process_failed_title') || 'Process failed',
                                description: Lang.get('category.process_failed_description') || 'Invalid data. Please check your inputs.',
                                subtitle: 'Code: ' + ' ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: Lang.get('category.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('category.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('category.system_error_description'),
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

    globalThis.categoryTable = new DataTable('#categoryTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[0, 'asc']],
        ajax: {
            url: route('categories.data'),
            data: function (d) {
                d.category_id = $('#f_categoryName').val() || '';
                d.is_active = $('#f_isActive').val() || '';
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
                data: 'parent_name',
                name: 'parent.name',
                orderable: false,
                searchable: false,
            },
            {
                data: 'description',
                name: 'description',
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

        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: 'pageLength',
            bottomEnd: 'paging',
        },
    });

    $('#custom-search-input').on('keyup', function () {
        categoryTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        $('#f_categoryName, #f_isActive').val('').trigger('change.select2');
        categoryTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        categoryTable.ajax.reload();
    });

    $.getJSON(route('categories.filter_data'))
        .done(function (res) {
            renderOptions('#f_categoryName', res.categoryName);
            renderOptions('#f_isActive', res.isActive);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('click', '#delete-category-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');

        if (!confirm(Lang.get('category.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                categoryTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('category.delete_toast_title'),
                    description: Lang.get('category.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('category.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('category.generic_error_description'),
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

    $(document).on('click', '#create-category', function () {
        openCategoryModal(route('categories.create'));
    });

    $(document).on('click', '#edit-category-btn', function () {
        let editUrl = $(this).data('edit-url');
        openCategoryModal(editUrl);
    });

    handleCategoryFormSubmit('#form-create-category, #form-edit-category');
});
