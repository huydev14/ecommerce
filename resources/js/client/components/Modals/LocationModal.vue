<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '@/services/api';
import { useLocationStore } from '@/stores/location';

const emit = defineEmits(['close']);

const locationStore = useLocationStore();
const provinces = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');
const searchTerm = ref('');
const showContent = ref(false);

const filteredProvinces = computed(() => {
    const keyword = searchTerm.value.trim().toLowerCase();
    if (!keyword) return provinces.value;
    return provinces.value.filter((province) => getProvinceName(province).toLowerCase().includes(keyword));
});

const getProvinceName = (province) => province?.ProvinceName || '';

const fetchProvinces = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    try {
        const response = await api.get('/locations/provinces');
        provinces.value = Array.isArray(response.data?.data) ? response.data.data : [];
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Không thể tải danh sách tỉnh thành.';
    } finally {
        isLoading.value = false;
    }
};

const selectProvince = (province) => {
    locationStore.setProvince(province);
    emit('close');
};

onMounted(() => {
    showContent.value = true;
    fetchProvinces();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="tw-transition tw-duration-200 tw-ease-out"
            enter-from-class="tw-opacity-0"
            enter-to-class="tw-opacity-100"
            leave-active-class="tw-transition tw-duration-150 tw-ease-in"
            leave-from-class="tw-opacity-100"
            leave-to-class="tw-opacity-0"
        >
            <div 
                class="tw-fixed tw-inset-0 tw-z-[1000] tw-flex tw-items-center tw-justify-center tw-bg-black/60 tw-p-4 tw-backdrop-blur-sm" 
                @click.self="emit('close')"
            >
                <Transition
                    enter-active-class="tw-transition tw-duration-300 tw-ease-out"
                    enter-from-class="tw-opacity-0 tw-translate-y-4 tw-scale-95"
                    enter-to-class="tw-opacity-100 tw-translate-y-0 tw-scale-100"
                    leave-active-class="tw-transition tw-duration-200 tw-ease-in"
                    leave-from-class="tw-opacity-100 tw-translate-y-0 tw-scale-100"
                    leave-to-class="tw-opacity-0 tw-translate-y-4 tw-scale-95"
                >
                    <div v-show="showContent" class="tw-w-full tw-max-w-[400px] tw-overflow-hidden tw-rounded-xl tw-bg-white tw-text-[#0f1111] tw-shadow-2xl">
                        
                        <div class="tw-flex tw-items-center tw-justify-between tw-border-b tw-border-gray-200 tw-bg-gray-50/50 tw-px-5 tw-py-3.5">
                            <h2 class="tw-m-0 tw-text-[16px] tw-font-bold tw-text-gray-800">Chọn vị trí giao hàng</h2>
                            <button
                                type="button"
                                class="tw-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-full tw-text-gray-400 tw-transition-colors hover:tw-bg-gray-200 hover:tw-text-gray-700 focus:tw-outline-none"
                                aria-label="Đóng"
                                @click="emit('close')"
                            >
                                <svg class="tw-h-5 tw-w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="tw-p-5">
                            
                            <div class="tw-relative tw-mb-4">
                                <div class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-left-0 tw-flex tw-items-center tw-pl-3">
                                    <svg class="tw-h-4 tw-w-4 tw-text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchTerm"
                                    type="text"
                                    placeholder="Tìm tỉnh / thành phố..."
                                    class="tw-w-full tw-rounded-lg tw-border tw-border-gray-300 tw-bg-gray-50 tw-py-2.5 tw-pl-9 tw-pr-3 tw-text-[14px] tw-transition-all focus:tw-border-[#f3a847] focus:tw-bg-white focus:tw-outline-none focus:tw-ring-1 focus:tw-ring-[#f3a847]"
                                />
                            </div>

                            <div v-if="isLoading" class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-10 tw-text-gray-500">
                                <svg class="tw-h-6 tw-w-6 tw-animate-spin tw-text-[#f3a847]" fill="none" viewBox="0 0 24 24">
                                    <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="tw-mt-2 tw-text-[13px]">Đang tải dữ liệu...</span>
                            </div>
                            
                            <div v-else-if="errorMessage" class="tw-rounded-lg tw-bg-red-50 tw-p-4 tw-text-center tw-text-[13px] tw-text-red-600 tw-border tw-border-red-100">
                                {{ errorMessage }}
                            </div>
                            
                            <div v-else class="custom-scrollbar tw-max-h-[340px] tw-overflow-y-auto tw-rounded-lg tw-border tw-border-gray-200">
                                <button
                                    v-for="province in filteredProvinces"
                                    :key="province.ProvinceID"
                                    type="button"
                                    class="tw-group tw-flex tw-w-full tw-items-center tw-justify-between tw-border-b tw-border-gray-100 tw-px-4 tw-py-3 tw-text-left tw-text-[14px] tw-transition-colors last:tw-border-b-0 hover:tw-bg-[#f7fafa]"
                                    :class="{ 'tw-bg-[#f0f8f8] hover:tw-bg-[#e8f3f3]': locationStore.currentProvinceId == province.ProvinceID }"
                                    @click="selectProvince(province)"
                                >
                                    <span :class="{ 'tw-font-medium tw-text-[#007185]': locationStore.currentProvinceId == province.ProvinceID }">
                                        {{ getProvinceName(province) }}
                                    </span>
                                    
                                    <svg 
                                        v-if="locationStore.currentProvinceId == province.ProvinceID"
                                        class="tw-h-5 tw-w-5 tw-text-[#007185]" 
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div v-if="!filteredProvinces.length" class="tw-py-10 tw-text-center tw-text-[14px] tw-text-gray-500">
                                    Không tìm thấy kết quả phù hợp.
                                </div>
                            </div>

                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>