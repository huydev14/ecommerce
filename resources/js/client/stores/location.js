import { defineStore } from 'pinia';

const STORAGE_KEYS = {
    name: 'current_location_name',
    provinceId: 'current_location_province_id',
};

export const useLocationStore = defineStore('location', {
    state: () => ({
        currentLocationName: localStorage.getItem(STORAGE_KEYS.name) || 'TP. Hồ Chí Minh',
        currentProvinceId: localStorage.getItem(STORAGE_KEYS.provinceId) || null,
    }),

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
