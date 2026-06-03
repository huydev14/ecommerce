import { defineStore } from 'pinia';

const STORAGE_KEYS = {
    name: 'current_location_name',
    provinceId: 'current_location_province_id',
};

const DEFAULT_LOCATION = {
    name: 'Hồ Chí Minh',
    provinceId: '202',
};

const getInitialLocationState = () => {
    const storedName = localStorage.getItem(STORAGE_KEYS.name);
    const storedProvinceId = localStorage.getItem(STORAGE_KEYS.provinceId);

    if (!storedName) {
        localStorage.setItem(STORAGE_KEYS.name, DEFAULT_LOCATION.name);
    }

    if (!storedProvinceId) {
        localStorage.setItem(STORAGE_KEYS.provinceId, DEFAULT_LOCATION.provinceId);
    }

    return {
        currentLocationName: storedName || DEFAULT_LOCATION.name,
        currentProvinceId: storedProvinceId || DEFAULT_LOCATION.provinceId,
    };
};

export const useLocationStore = defineStore('location', {
    state: getInitialLocationState,

    actions: {
        setProvince(province) {
            const name = province?.ProvinceName || '';
            const provinceId = province?.ProvinceID || null;

            if (!name || !provinceId) {
                return;
            }

            this.currentLocationName = name;
            this.currentProvinceId = provinceId;

            localStorage.setItem(STORAGE_KEYS.name, name);
            localStorage.setItem(STORAGE_KEYS.provinceId, String(provinceId));
        },
    },
});
