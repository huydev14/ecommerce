$(function () {
    function toastSuccess(description, statusCode) {
        fluentToast({
            type: 'success',
            title: Lang.get('customer_address.success_title'),
            description: description,
            subtitle: 'Code: ' + statusCode,
            actionType: 'close',
        });
    }

    function openCustomerAddressModal(url) {
        ModalHelper.open('modal');
        $('#customer-address-modal-content').html(loadingHtml);

        $.get(url, function (html) {
            $('#customer-address-modal-content').html(html);
            initCustomerAddressLocationFields($('#customer-address-modal-content'));
        }).fail(function (xhr) {
            $('#customer-address-modal-content').html(loadingHtml);
            console.error('Load customer address modal error:', xhr.status);
            console.error('Load customer address modal error:', xhr.responseText);
        });
    }

    function getSelectedOptionText(select) {
        return select.find('option:selected').text().trim();
    }

    function getProvinceName(province) {
        return province.ProvinceName || province.province_name || province.name || '';
    }

    function getProvinceId(province) {
        return province.ProvinceID || province.province_id || province.id || '';
    }

    function getDistrictName(district) {
        return district.DistrictName || district.district_name || district.name || '';
    }

    function getDistrictId(district) {
        return district.DistrictID || district.district_id || district.id || '';
    }

    function getWardName(ward) {
        return ward.WardName || ward.ward_name || ward.name || '';
    }

    function getWardCode(ward) {
        return ward.WardCode || ward.ward_code || ward.code || '';
    }

    function resetLocationSelect(select, placeholder, isDisabled = true) {
        select.html('<option value="">' + placeholder + '</option>').prop('disabled', isDisabled);
    }

    function renderDistrictOptions(districtSelect, districts, selectedDistrictId) {
        let placeholder = Lang.get('customer_address.district_placeholder') || 'Select district';
        let options = ['<option value="">' + placeholder + '</option>'];

        districts.forEach(function (district) {
            let districtId = getDistrictId(district);
            let districtName = getDistrictName(district);
            let selected = String(selectedDistrictId || '') === String(districtId) ? ' selected' : '';

            options.push('<option value="' + districtId + '"' + selected + '>' + districtName + '</option>');
        });

        districtSelect.html(options.join('')).prop('disabled', false);
    }

    function renderWardOptions(wardSelect, wards, selectedWardCode) {
        let placeholder = Lang.get('customer_address.ward_placeholder') || 'Select ward';
        let options = ['<option value="">' + placeholder + '</option>'];

        wards.forEach(function (ward) {
            let wardCode = getWardCode(ward);
            let wardName = getWardName(ward);
            let selected = String(selectedWardCode || '') === String(wardCode) ? ' selected' : '';

            options.push('<option value="' + wardCode + '"' + selected + '>' + wardName + '</option>');
        });

        wardSelect.html(options.join('')).prop('disabled', false);
    }

    function loadDistricts(form, provinceId, selectedDistrictId, selectedWardCode) {
        let districtSelect = form.find('#district_id');
        let wardSelect = form.find('#ward_code');
        let districtPlaceholder = Lang.get('customer_address.district_placeholder') || 'Select district';
        let wardPlaceholder = Lang.get('customer_address.ward_placeholder') || 'Select ward';

        resetLocationSelect(districtSelect, Lang.get('customer_address.loading') || 'Loading...', true);
        resetLocationSelect(wardSelect, wardPlaceholder, true);
        form.find('#district_name, #ward_name').val('');

        if (!provinceId) {
            resetLocationSelect(districtSelect, districtPlaceholder, true);
            return;
        }

        $.getJSON(route('customer-addresses.districts'), { province_id: provinceId })
            .done(function (res) {
                renderDistrictOptions(districtSelect, res.data || [], selectedDistrictId);
                form.find('#district_name').val(getSelectedOptionText(districtSelect));

                if (selectedDistrictId) {
                    loadWards(form, selectedDistrictId, selectedWardCode);
                }
            })
            .fail(function (xhr) {
                resetLocationSelect(districtSelect, districtPlaceholder, true);
                console.error('Load districts error:', xhr.status);
                console.error('Load districts error:', xhr.responseText);
            });
    }

    function loadWards(form, districtId, selectedWardCode) {
        let wardSelect = form.find('#ward_code');
        let wardPlaceholder = Lang.get('customer_address.ward_placeholder') || 'Select ward';

        resetLocationSelect(wardSelect, Lang.get('customer_address.loading') || 'Loading...', true);
        form.find('#ward_name').val('');

        if (!districtId) {
            resetLocationSelect(wardSelect, wardPlaceholder, true);
            return;
        }

        $.getJSON(route('customer-addresses.wards'), { district_id: districtId })
            .done(function (res) {
                renderWardOptions(wardSelect, res.data || [], selectedWardCode);
                form.find('#ward_name').val(getSelectedOptionText(wardSelect));
            })
            .fail(function (xhr) {
                resetLocationSelect(wardSelect, wardPlaceholder, true);
                console.error('Load wards error:', xhr.status);
                console.error('Load wards error:', xhr.responseText);
            });
    }

    function initCustomerAddressLocationFields(container) {
        let form = container.find('#form-create-customer-address, #form-edit-customer-address');

        if (!form.length) {
            return;
        }

        let locationFields = form.find('.customer-address-location-fields');
        let selectedDistrictId = locationFields.data('selected-district-id') || '';
        let selectedWardCode = locationFields.data('selected-ward-code') || '';
        let provinceSelect = form.find('#province_id');

        form.find('#province_name').val(getSelectedOptionText(provinceSelect));

        if (provinceSelect.val()) {
            loadDistricts(form, provinceSelect.val(), selectedDistrictId, selectedWardCode);
        }
    }

    function handleCustomerAddressFormSubmit(formSelector) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            let originalBtnText = submitBtn.html();

            submitBtn
                .html('<i class="fas fa-spinner fa-spin tw-mr-2"></i> ' + (Lang.get('customer_address.save_loading') || 'Saving...'))
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
                        customerAddressTable.ajax.reload(null, false);
                        toastSuccess(res.message, xhr.status);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON?.errors || {};

                        if (!Object.keys(errors).length) {
                            fluentToast({
                                type: 'error',
                                title: Lang.get('customer_address.process_failed_title'),
                                description: Lang.get('customer_address.process_failed_description'),
                                subtitle: 'Code: ' + xhr.status,
                                actionType: 'close',
                            });
                            return;
                        }

                        let firstErrorMsg = Object.values(errors)[0][0];
                        fluentToast({
                            type: 'error',
                            title: Lang.get('customer_address.process_failed_title'),
                            description: firstErrorMsg,
                            subtitle: 'Code: ' + xhr.status,
                            actionType: 'close',
                        });
                    } else {
                        fluentToast({
                            type: 'error',
                            title: Lang.get('customer_address.system_error_title'),
                            description: xhr.responseJSON?.message || Lang.get('customer_address.system_error_description'),
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

    globalThis.customerAddressTable = new DataTable('#customerAddressTable', {
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[5, 'desc']],
        ajax: {
            url: route('customer-addresses.data'),
            data: function (d) {
                d.customer_id = $('#f_customer').val() || '';
                d.is_default = $('#f_isDefault').val() || '';
            },
        },
        columns: [
            {
                data: 'customer',
                name: 'customer.name',
                orderable: false,
                searchable: false,
            },
            {
                data: 'receiver_name',
                name: 'receiver_name',
            },
            {
                data: 'receiver_phone',
                name: 'receiver_phone',
            },
            {
                data: 'full_address',
                name: 'specific_address',
                orderable: false,
                searchable: false,
            },
            {
                data: 'is_default',
                name: 'is_default',
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
        customerAddressTable.search(this.value).draw();
    });

    $('#toggle-filter-btn').on('click', function () {
        $('#filter-panel').slideToggle('fast');

        $('#f_customer').val('').trigger('change.select2');
        $('#f_isDefault').val('').trigger('change.select2');
        customerAddressTable.ajax.reload();
    });

    $(document).on('change', '#filter-panel select', function () {
        customerAddressTable.ajax.reload();
    });

    $(document).on('change', '#province_id', function () {
        let form = $(this).closest('form');

        form.find('#province_name').val(getSelectedOptionText($(this)));
        loadDistricts(form, $(this).val(), '', '');
    });

    $(document).on('change', '#district_id', function () {
        let form = $(this).closest('form');

        form.find('#district_name').val(getSelectedOptionText($(this)));
        loadWards(form, $(this).val(), '');
    });

    $(document).on('change', '#ward_code', function () {
        let form = $(this).closest('form');

        form.find('#ward_name').val(getSelectedOptionText($(this)));
    });

    $.getJSON(route('customer-addresses.filter_data'))
        .done(function (res) {
            renderOptions('#f_customer', res.customers);
            renderOptions('#f_isDefault', res.default_status);
        })
        .fail(function (xhr) {
            console.error('Load error:', xhr.status);
            console.error('Load error:', xhr.responseText);
        });

    $(document).on('click', '#delete-customer-address-btn', function () {
        let $btn = $(this);
        let deleteUrl = $btn.data('delete-url');

        if (!confirm(Lang.get('customer_address.confirm_delete'))) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            type: 'DELETE',
            url: deleteUrl,
            success: function (res) {
                customerAddressTable.ajax.reload(null, false);
                fluentToast({
                    type: 'info',
                    title: Lang.get('customer_address.delete_toast_title'),
                    description: res.message || Lang.get('customer_address.delete_description'),
                    subtitle: res.status,
                    actionType: 'close',
                });
            },
            error: function (xhr) {
                fluentToast({
                    type: 'error',
                    title: Lang.get('customer_address.generic_error_title'),
                    description: xhr.responseJSON?.message || Lang.get('customer_address.generic_error_description'),
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

    $(document).on('click', '#create-customer-address', function () {
        openCustomerAddressModal(route('customer-addresses.create'));
    });

    $(document).on('click', '#edit-customer-address-btn', function () {
        let editUrl = $(this).data('edit-url');
        openCustomerAddressModal(editUrl);
    });

    handleCustomerAddressFormSubmit('#form-create-customer-address, #form-edit-customer-address');
});
