$(function () {
    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: 'Thành công',
            description: description,
            subtitle: 'Mã: ' + statusCode,
            actionType: 'close',
        });
    }

    function openBannerModal(url) {
        ModalHelper.open('modal');
        $('#banner-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#banner-modal-content').html(html);
        }).fail(function (xhr) {
            $('#banner-modal-content').html(loadingHtml);
            console.error('Load banner modal error:', xhr.status);
            console.error('Load banner modal error:', xhr.responseText);
        });
    }

    function handleBannerFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            let originalBtnText = submitBtn.html();

            submitBtn.html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> Đang lưu...').prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res, textStatus, xhr) {
                    if (res.success) {
                        ModalHelper.close('modal');
                        bannerTable.ajax.reload(null, false);
                        toastSuccess(res.msg, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: 'Xử lý thất bại',
                                description: 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại các trường nhập.',
                                subtitle: 'Mã: ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: 'Xử lý thất bại',
                            description: firstErrorMsg,
                            subtitle: 'Mã: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: 'Lỗi hệ thống',
                            description: xhr.responseJSON?.msg || 'Đã có lỗi hệ thống xảy ra!',
                            subtitle: 'Mã: ' + xhr.status,
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

    globalThis.bannerTable = new DataTable('#bannerTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[3, 'asc']],
        ajax: {
            url: route('banners.data'),
        },
        columns: [
            {
                data: 'title',
                name: 'title',
            },
            {
                data: 'image_url',
                name: 'image_url',
            },
            {
                data: 'link',
                name: 'link',
            },
            {
                data: 'sort_order',
                name: 'sort_order',
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
            let url = route('banners.edit', data.id);

            $(row)
                .css('cursor', 'pointer')
                .on('click', function (e) {
                    if ($(e.target).closest('button').length > 0) {
                        return;
                    }

                    openBannerModal(url);
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
        bannerTable.search(this.value).draw();
    });

    $(document).on('click', '#create-banner', function () {
        openBannerModal(route('banners.create'));
    });

    $(document).on('click', '#edit-banner-btn', function () {
        let editUrl = $(this).data('edit-url');
        openBannerModal(editUrl);
    });

    $(document).on('click', '#delete-banner-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');

        if (!confirm('Xác nhận xóa banner này?')) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                bannerTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: 'Đã xóa banner',
                    description: 'Banner đã được xóa khỏi hệ thống.',
                    subtitle: res.status,
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: 'Lỗi hệ thống',
                    description: xhr.responseJSON?.msg || 'Đã có lỗi hệ thống xảy ra!',
                    subtitle: 'Mã: ' + xhr.status,
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

    handleBannerFormSubmit('#form-create-banner, #form-edit-banner');
});

globalThis.previewBannerImage = function (input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function (e) {
            $('#banner-image-preview').attr('src', e.target.result).removeClass('tw-hidden');
            $('#banner-image-placeholder').addClass('tw-hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
};
