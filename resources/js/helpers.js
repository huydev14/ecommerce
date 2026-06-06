globalThis.loadingHtml = `
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-h-full tw-min-h-[300px] tw-text-gray-500">
        <i class="fas fa-spinner fa-spin tw-text-4xl tw-text-[#0063B1] tw-mb-4"></i>
        <p class="tw-text-sm">Đang tải dữ liệu...</p>
    </div>
`;

$(function () {
    $(
        '#f_status, #f_department, #f_employment_type, #f_role, #f_logName, #f_causer, #f_brandName, #f_categoryName, #f_isActive, #f_productName, #f_category, #f_brand, #f_product, #f_taxName, #f_unitName, #f_warehouse,#f_variant, #f_stock, #f_type, #f_orderStatus, #f_paymentStatus, #f_paymentMethod, #f_customer, #f_isFeatured',
    ).select2({
        theme: 'bootstrap4',
        minimumResultsForSearch: 5,
        width: '100%',
    });
});

globalThis.renderOptions = function (selector, items) {
    let $selector = $(selector);
    if (!items) items = [];

    // Reset select
    $selector.find('option:not([value=""])').remove();
    $selector.val('');

    let html = '';
    items.forEach((item) => {
        html += `<option value="${item.id}">${item.text}</option>`;
    });
    $selector.append(html);
};

globalThis.ModalHelper = {
    open: function (modal_id) {
        const $modal = $('#' + modal_id);

        if (!$modal.length) {
            console.error('Không tìm thấy Modal với ID: ' + modal_id);
            return;
        }

        $modal.removeClass('tw-hidden').addClass('tw-flex');
        $('body').css('overflow', 'hidden');
    },

    close: function (modal_id) {
        const $modal = $('#' + modal_id);
        if (!$modal.length) return;

        $modal.addClass('tw-hidden').removeClass('tw-flex');
        $('body').css('overflow', '');
    },
};

$(function () {
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.tw-fixed:not(.tw-hidden)').each(function () {
                ModalHelper.close($(this).attr('id'));
            });
        }
    });

    $(document).on('click', function (e) {
        const $target = $(e.target);
        if ($target.hasClass('tw-fixed') && $target.hasClass('tw-bg-gray-900/40')) {
            ModalHelper.close($target.attr('id'));
        }
    });
});

globalThis.previewBrandLogo = function (input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function (e) {
            $('#logo-preview').attr('src', e.target.result).removeClass('tw-hidden');
            $('#logo-placeholder').addClass('tw-hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
};

$(function () {
    let params = new URLSearchParams(globalThis.location.search);
    let activeTab = params.get('tab');

    if (activeTab) {
        let tabTriggerEl = document.querySelector('#v-pills-' + activeTab + '-tab');
        if (tabTriggerEl) {
            bootstrap.Tab.getOrCreateInstance(tabTriggerEl).show();
        }
    }

    $('.fluent-tab-item').on('shown.bs.tab', function (e) {
        let tabId = $(e.target).attr('data-tab');
        let newUrl = globalThis.location.pathname + '?tab=' + tabId;

        globalThis.history.replaceState(null, null, newUrl);
    });
});
