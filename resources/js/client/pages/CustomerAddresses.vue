<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';

const { t } = useI18n();
const defaultAddressForm = () => ({
    id: null,
    label: '',
    receiver_name: '',
    receiver_phone: '',
    province_id: '',
    district_id: '',
    ward_code: '',
    province_name: '',
    district_name: '',
    ward_name: '',
    specific_address: '',
    delivery_note: '',
    is_default: false,
});

const addresses = ref([]);
const provinces = ref([]);
const districts = ref([]);
const wards = ref([]);
const form = reactive(defaultAddressForm());
const isLoading = ref(false);
const isSaving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const isEditing = computed(() => Boolean(form.id));

const getProvinceId = (province) => province.ProvinceID || province.province_id || province.id;
const getProvinceName = (province) => province.ProvinceName || province.province_name || province.name || '';
const getDistrictId = (district) => district.DistrictID || district.district_id || district.id;
const getDistrictName = (district) => district.DistrictName || district.district_name || district.name || '';
const getWardCode = (ward) => ward.WardCode || ward.ward_code || ward.code;
const getWardName = (ward) => ward.WardName || ward.ward_name || ward.name || '';

const fullAddress = (address) =>
    [address.specific_address, address.ward_name, address.district_name, address.province_name].filter(Boolean).join(', ');

const addressTitle = (address) => address.label || (address.is_default ? t('addresses.defaultAddress') : t('addresses.shippingAddress'));

const extractError = (error, fallback) => {
    if (error.response?.data?.message) {
        return error.response.data.message;
    }

    const validationErrors = error.response?.data?.errors;
    if (validationErrors && Object.keys(validationErrors).length > 0) {
        return Object.values(validationErrors)[0][0];
    }

    return fallback;
};

const resetForm = () => {
    Object.assign(form, defaultAddressForm());
    districts.value = [];
    wards.value = [];
    errorMessage.value = '';
};

const fetchAddresses = async () => {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get('/customer-addresses');
        addresses.value = response.data.data || [];
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_loadAddresses'));
    } finally {
        isLoading.value = false;
    }
};

const fetchProvinces = async () => {
    const response = await api.get('/locations/provinces');
    provinces.value = response.data.data || [];
};

const loadDistricts = async (provinceId, selectedDistrictId = '') => {
    districts.value = [];
    wards.value = [];
    form.district_id = selectedDistrictId || '';
    form.district_name = '';
    form.ward_code = '';
    form.ward_name = '';

    if (!provinceId) {
        return;
    }

    const response = await api.get('/locations/districts', {
        params: { province_id: provinceId },
    });
    districts.value = response.data.data || [];

    if (selectedDistrictId) {
        const selectedDistrict = districts.value.find((district) => String(getDistrictId(district)) === String(selectedDistrictId));
        form.district_name = selectedDistrict ? getDistrictName(selectedDistrict) : form.district_name;
    }
};

const loadWards = async (districtId, selectedWardCode = '') => {
    wards.value = [];
    form.ward_code = selectedWardCode || '';
    form.ward_name = '';

    if (!districtId) {
        return;
    }

    const response = await api.get('/locations/wards', {
        params: { district_id: districtId },
    });
    wards.value = response.data.data || [];

    if (selectedWardCode) {
        const selectedWard = wards.value.find((ward) => String(getWardCode(ward)) === String(selectedWardCode));
        form.ward_name = selectedWard ? getWardName(selectedWard) : form.ward_name;
    }
};

const handleProvinceChange = async () => {
    const selectedProvince = provinces.value.find((province) => String(getProvinceId(province)) === String(form.province_id));
    form.province_name = selectedProvince ? getProvinceName(selectedProvince) : '';

    try {
        await loadDistricts(form.province_id);
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_loadDistricts'));
    }
};

const handleDistrictChange = async () => {
    const selectedDistrict = districts.value.find((district) => String(getDistrictId(district)) === String(form.district_id));
    form.district_name = selectedDistrict ? getDistrictName(selectedDistrict) : '';

    try {
        await loadWards(form.district_id);
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_loadWards'));
    }
};

const handleWardChange = () => {
    const selectedWard = wards.value.find((ward) => String(getWardCode(ward)) === String(form.ward_code));
    form.ward_name = selectedWard ? getWardName(selectedWard) : '';
};

const editAddress = async (address) => {
    Object.assign(form, {
        id: address.id,
        label: address.label || '',
        receiver_name: address.receiver_name || '',
        receiver_phone: address.receiver_phone || '',
        province_id: address.province_id || '',
        district_id: address.district_id || '',
        ward_code: address.ward_code || '',
        province_name: address.province_name || '',
        district_name: address.district_name || '',
        ward_name: address.ward_name || '',
        specific_address: address.specific_address || '',
        delivery_note: address.delivery_note || '',
        is_default: Boolean(address.is_default),
    });

    try {
        await loadDistricts(form.province_id, form.district_id);
        await loadWards(form.district_id, form.ward_code);
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_loadLocationData'));
    }
};

const saveAddress = async () => {
    isSaving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    const payload = {
        label: form.label,
        receiver_name: form.receiver_name,
        receiver_phone: form.receiver_phone,
        province_id: Number(form.province_id),
        district_id: Number(form.district_id),
        ward_code: String(form.ward_code),
        province_name: form.province_name,
        district_name: form.district_name,
        ward_name: form.ward_name,
        specific_address: form.specific_address,
        delivery_note: form.delivery_note,
        is_default: Boolean(form.is_default),
    };

    try {
        const response = form.id
            ? await api.put(`/customer-addresses/${form.id}`, payload)
            : await api.post('/customer-addresses', payload);

        successMessage.value = response.data.message || t('addresses.messages_saved');
        resetForm();
        await fetchAddresses();
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_saveAddress'));
    } finally {
        isSaving.value = false;
    }
};

const setDefaultAddress = async (address) => {
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await api.patch(`/customer-addresses/${address.id}/default`);
        successMessage.value = response.data.message || t('addresses.messages_defaultUpdated');
        await fetchAddresses();
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_updateDefault'));
    }
};

const removeAddress = async (address) => {
    if (!confirm(t('addresses.confirmDelete'))) {
        return;
    }

    errorMessage.value = '';
    successMessage.value = '';

    try {
        const response = await api.delete(`/customer-addresses/${address.id}`);
        successMessage.value = response.data.message || t('addresses.messages_deleted');

        if (form.id === address.id) {
            resetForm();
        }

        await fetchAddresses();
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_deleteAddress'));
    }
};

onMounted(async () => {
    try {
        await Promise.all([fetchAddresses(), fetchProvinces()]);
    } catch (error) {
        errorMessage.value = extractError(error, t('addresses.errors_initialize'));
    }
});
</script>

<template>
    <section class="addresses-page" aria-labelledby="addresses-title">
        <div class="addresses-page__inner">
            <header class="addresses-header">
                <div>
                    <p class="addresses-eyebrow">{{ t('addresses.account') }}</p>
                    <h1 id="addresses-title">{{ t('addresses.title') }}</h1>
                </div>
                <router-link :to="{ name: 'MyAccountOrders' }" class="addresses-header__link">{{ t('addresses.viewOrders') }}</router-link>
            </header>

            <div v-if="errorMessage" class="address-alert is-error" role="alert">{{ errorMessage }}</div>
            <div v-if="successMessage" class="address-alert is-success" role="status">{{ successMessage }}</div>

            <div class="addresses-layout">
                <section class="addresses-list" :aria-label="t('addresses.aria_saved')">
                    <div v-if="isLoading" class="addresses-empty">{{ t('addresses.loading') }}</div>
                    <div v-else-if="!addresses.length" class="addresses-empty">{{ t('addresses.empty') }}</div>

                    <template v-else>
                        <article v-for="address in addresses" :key="address.id" class="address-card" :class="{ 'is-default': address.is_default }">
                            <div class="address-card__top">
                                <div>
                                    <h2>{{ addressTitle(address) }}</h2>
                                    <span v-if="address.is_default">{{ t('addresses.defaultAddress') }}</span>
                                </div>
                                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            </div>

                            <address>
                                <strong>{{ address.receiver_name }}</strong>
                                <span>{{ address.receiver_phone }}</span>
                                <span>{{ address.specific_address }}</span>
                                <span>{{ address.ward_name }}, {{ address.district_name }}</span>
                                <span>{{ address.province_name }}</span>
                            </address>

                            <p class="address-card__note">{{ fullAddress(address) }}</p>
                            <p v-if="address.delivery_note" class="address-card__note">{{ address.delivery_note }}</p>

                            <div class="address-card__actions">
                                <button type="button" @click="editAddress(address)">{{ t('addresses.actions_edit') }}</button>
                                <button v-if="!address.is_default" type="button" @click="setDefaultAddress(address)">
                                    {{ t('addresses.actions_setDefault') }}
                                </button>
                                <button type="button" class="is-danger" @click="removeAddress(address)">{{ t('addresses.actions_delete') }}</button>
                            </div>
                        </article>
                    </template>
                </section>

                <form class="address-form" @submit.prevent="saveAddress">
                    <div class="address-form__header">
                        <h2>{{ isEditing ? t('addresses.form_editTitle') : t('addresses.form_addTitle') }}</h2>
                        <button v-if="isEditing" type="button" @click="resetForm">{{ t('addresses.actions_cancel') }}</button>
                    </div>

                    <div class="address-form__row">
                        <label>
                            {{ t('addresses.form_label') }}
                            <input v-model.trim="form.label" type="text" maxlength="50" :placeholder="t('addresses.form_labelPlaceholder')" required />
                        </label>
                        <label>
                            {{ t('addresses.form_receiverName') }}
                            <input v-model.trim="form.receiver_name" type="text" required />
                        </label>
                    </div>

                    <div class="address-form__row">
                        <label>
                            {{ t('addresses.form_phone') }}
                            <input v-model.trim="form.receiver_phone" type="tel" required />
                        </label>
                        <label>
                            {{ t('addresses.form_deliveryNote') }}
                            <input v-model.trim="form.delivery_note" type="text" maxlength="255" :placeholder="t('addresses.form_deliveryNotePlaceholder')" />
                        </label>
                    </div>

                    <label>
                        {{ t('addresses.form_streetAddress') }}
                        <input v-model.trim="form.specific_address" type="text" required />
                    </label>

                    <div class="address-form__row is-three">
                        <label>
                            {{ t('addresses.form_province') }}
                            <select v-model="form.province_id" required @change="handleProvinceChange">
                                <option value="">{{ t('addresses.form_selectProvince') }}</option>
                                <option v-for="province in provinces" :key="getProvinceId(province)" :value="getProvinceId(province)">
                                    {{ getProvinceName(province) }}
                                </option>
                            </select>
                        </label>
                        <label>
                            {{ t('addresses.form_district') }}
                            <select v-model="form.district_id" required :disabled="!districts.length" @change="handleDistrictChange">
                                <option value="">{{ t('addresses.form_selectDistrict') }}</option>
                                <option v-for="district in districts" :key="getDistrictId(district)" :value="getDistrictId(district)">
                                    {{ getDistrictName(district) }}
                                </option>
                            </select>
                        </label>
                        <label>
                            {{ t('addresses.form_ward') }}
                            <select v-model="form.ward_code" required :disabled="!wards.length" @change="handleWardChange">
                                <option value="">{{ t('addresses.form_selectWard') }}</option>
                                <option v-for="ward in wards" :key="getWardCode(ward)" :value="getWardCode(ward)">
                                    {{ getWardName(ward) }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <label class="address-form__check">
                        <input v-model="form.is_default" type="checkbox" />
                        {{ t('addresses.form_useDefault') }}
                    </label>

                    <button type="submit" class="address-form__submit" :disabled="isSaving">
                        {{ isSaving ? t('addresses.actions_saving') : isEditing ? t('addresses.actions_saveChanges') : t('addresses.actions_addAddress') }}
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>

<style scoped>
.addresses-page {
    min-height: 100vh;
    background: #f4f6f8;
    color: #0f1111;
}

.addresses-page__inner {
    width: min(100%, 1240px);
    margin: 0 auto;
    padding: 28px 24px 72px;
}

.addresses-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
}

.addresses-eyebrow {
    margin: 0 0 6px;
    color: #565959;
    font-size: 14px;
    line-height: 20px;
}

.addresses-header h1 {
    margin: 0;
    color: #0f1111;
    font-size: 34px;
    font-weight: 700;
    line-height: 42px;
    letter-spacing: 0;
}

.addresses-header__link {
    color: #007185;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
}

.addresses-header__link:hover {
    color: #c45500;
    text-decoration: underline;
}

.addresses-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 420px;
    gap: 22px;
    align-items: start;
}

.address-alert,
.addresses-empty {
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #ffffff;
    padding: 14px 16px;
    font-size: 14px;
    line-height: 20px;
}

.address-alert {
    margin-bottom: 16px;
}

.address-alert.is-error {
    border-color: #f5c2c7;
    background: #fff5f5;
    color: #842029;
}

.address-alert.is-success {
    border-color: #badbcc;
    background: #f0fff4;
    color: #0f5132;
}

.addresses-empty {
    grid-column: 1 / -1;
    color: #565959;
}

.addresses-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.address-card,
.address-form {
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.06);
}

.address-card {
    display: grid;
    gap: 16px;
    min-height: 292px;
    padding: 20px;
}

.address-card.is-default {
    border-color: #f08804;
    box-shadow: 0 0 0 1px #f08804;
}

.address-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.address-card h2,
.address-form h2 {
    margin: 0;
    color: #0f1111;
    font-size: 20px;
    font-weight: 700;
    line-height: 26px;
    letter-spacing: 0;
}

.address-card__top span {
    display: inline-flex;
    margin-top: 8px;
    border-radius: 999px;
    background: #fff3cd;
    padding: 4px 10px;
    color: #7a4d00;
    font-size: 12px;
    font-weight: 700;
    line-height: 16px;
}

.address-card__top i {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border-radius: 50%;
    background: #e7f4f5;
    color: #007185;
    font-size: 17px;
}

.address-card address {
    display: grid;
    gap: 5px;
    margin: 0;
    color: #111827;
    font-size: 15px;
    font-style: normal;
    line-height: 21px;
}

.address-card__note {
    margin: 0;
    color: #565959;
    font-size: 14px;
    line-height: 20px;
}

.address-card__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-self: end;
}

.address-card__actions button,
.address-form__header button,
.address-form__submit {
    min-height: 36px;
    border: 1px solid #d5d9d9;
    border-radius: 18px;
    background: #ffffff;
    padding: 0 14px;
    color: #007185;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
}

.address-card__actions button:hover,
.address-form__header button:hover {
    background: #f7fafa;
    color: #c45500;
}

.address-card__actions .is-danger {
    color: #b42318;
}

.address-form {
    display: grid;
    gap: 16px;
    padding: 20px;
}

.address-form__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.address-form__row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.address-form__row.is-three {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.address-form label {
    display: grid;
    gap: 7px;
    color: #374151;
    font-size: 13px;
    font-weight: 700;
    line-height: 18px;
}

.address-form input:not([type='checkbox']),
.address-form select,
.address-form textarea {
    width: 100%;
    border: 1px solid #888c8c;
    border-radius: 7px;
    background: #ffffff;
    padding: 10px 12px;
    color: #0f1111;
    font: inherit;
    font-weight: 400;
    outline: none;
}

.address-form select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%23374151' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 12px 8px;
    padding-right: 36px;
}

.address-form input:not([type='checkbox']):focus,
.address-form select:focus,
.address-form textarea:focus {
    border-color: #007185;
    box-shadow: 0 0 0 3px rgba(0, 113, 133, 0.18);
}

.address-form select:disabled {
    background-color: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.address-form textarea {
    resize: vertical;
}

.address-form__check {
    display: flex !important;
    grid-template-columns: none;
    align-items: center;
    gap: 10px !important;
    color: #0f1111 !important;
    font-size: 14px !important;
    font-weight: 500 !important;
}

.address-form__check input {
    width: 16px;
    height: 16px;
    margin: 0;
    padding: 0;
    accent-color: #007185;
    cursor: pointer;
}

.address-form__submit {
    width: 100%;
    border: 0;
    background: #ffd814;
    color: #0f1111;
}

.address-form__submit:disabled {
    opacity: 0.7;
    cursor: wait;
}

.address-form__submit:hover {
    background: #f7ca00;
}

@media (max-width: 980px) {
    .addresses-layout {
        grid-template-columns: 1fr;
    }

    .addresses-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .addresses-page__inner {
        padding: 22px 14px 56px;
    }

    .addresses-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .addresses-header h1 {
        font-size: 28px;
        line-height: 36px;
    }

    .address-form__row,
    .address-form__row.is-three {
        grid-template-columns: 1fr;
    }
}
</style>
