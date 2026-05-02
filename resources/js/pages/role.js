$(function () {
    $('.accordion-header').on('click keydown', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
            return;
        }
        if (e.type === 'keydown') {
            e.preventDefault();
        }
        openAccordion(this);
    });

    $('.permission-group').on('change', 'input[name="check_all"]', function () {
        let isChecked = $(this).prop('checked');
        $(this).closest('.permission-group').find('input[name="permissions[]"]').prop('checked', isChecked);
        updateGlobalCheckButton();
    });

    $('.permission-group').on('change', 'input[name="permissions[]"]', function () {
        let $group = $(this).closest('.permission-group');
        let total = $group.find('input[name="permissions[]"]').length;
        let checked = $group.find('input[name="permissions[]"]:checked').length;

        $group.find('input[name="check_all"]').prop('checked', total > 0 && total === checked);
        updateGlobalCheckButton();
    });

    let globalToggle = false;
    $('#btn-check-all-global').on('click', function () {
        globalToggle = !globalToggle;
        $('input[name="permissions[]"], input[name="check_all"]').prop('checked', globalToggle);
        $(this).text(globalToggle ? Lang.get('role.uncheck_all') : Lang.get('role.check_all'));
    });
    initCheckboxes();

    // --- SUBMIT FORM ------------------------
    $('#role-form').on('submit', function (e) {
        ajaxRoleFormRequest(e, this);
    });

    let ajaxRoleFormRequest = (e, formElement) => {
        e.preventDefault();

        let $form = $(formElement);
        let $submitBtn = $form.find('button[type="submit"]');
        let originalText = $submitBtn.html();

        $form.find('.field-error').remove();
        $form
            .find('.tw-border-red-500')
            .removeClass('tw-border-red-500 focus:tw-border-red-500 focus:tw-ring-red-500')
            .addClass('tw-border-gray-300');

        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + Lang.get('role.processing'));

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res, textStatus, xhr) {
                if (res.success) {
                    window.location.href = res.redirect;
                }
            },
            error: function (xhr) {
                $submitBtn.prop('disabled', false).html(originalText);

                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        let input = $form.find(`[name="${inputName}"]`);
                        let errorMessage = messages[0];

                        if (input.length) {
                            let wrapper = input.parent();
                            input
                                .removeClass('tw-border-gray-300')
                                .addClass('tw-border-red-500 focus:tw-border-red-500 focus:tw-ring-red-500');

                            if (wrapper.find('.field-error').length === 0) {
                                wrapper.append(`
                                <span class="field-error tw-block tw-text-red-500 tw-text-xs tw-mt-1.5 tw-font-medium tw-flex tw-items-center">
                                    ${errorMessage}
                                </span>
                            `);
                            }
                        }
                    });

                    fluentToast({
                        type: 'error',
                        title: Lang.get('role.process_failed_title'),
                        description: Lang.get('role.process_failed_description'),
                        subtitle: 'Mã lỗi: ' + xhr.status,
                        actionType: 'close',
                    });
                }
            },
        });
    };

    function openAccordion(element) {
        let $header = $(element);
        let $body = $header.next('.accordion-body');
        let $icon = $header.find('.accordion-icon');

        $body.slideToggle(250);

        const isExpanded = $header.attr('aria-expanded') === 'true';
        $header.attr('aria-expanded', !isExpanded);

        $icon.toggleClass('tw-rotate-90');
    }

    function initCheckboxes() {
        $('.permission-group').each(function () {
            let total = $(this).find('input[name="permissions[]"]').length;
            let checked = $(this).find('input[name="permissions[]"]:checked').length;
            if (total > 0 && total === checked) {
                $(this).find('input[name="check_all"]').prop('checked', true);
            }
        });
        updateGlobalCheckButton();

        $('.permission-group').each(function () {
            if ($(this).find('input[name="permissions[]"]:checked').length > 0) {
                let $header = $(this).find('.accordion-header');
                $header.next('.accordion-body').show();
                $header.find('.accordion-icon').addClass('tw-rotate-90');
                $header.attr('aria-expanded', 'true');
            }
        });
    }

    function updateGlobalCheckButton() {
        let totalPerms = $('input[name="permissions[]"]').length;
        let checkedPerms = $('input[name="permissions[]"]:checked').length;
        globalToggle = totalPerms === checkedPerms && totalPerms > 0;
        $('#btn-check-all-global').text(globalToggle ? Lang.get('role.uncheck_all') : Lang.get('role.check_all'));
    }
});
