<div class="tw-bg-white tw-rounded-md tw-shadow-sm tw-border tw-border-[#edebe9]">
    <div class="tw-px-6 tw-py-4 tw-border-b tw-flex tw-justify-between tw-items-center">
        <h5 class="tw-text-lg tw-font-semibold tw-text-[#323130] tw-mb-0">GHN API Configuration</h5>
        <span
            class="tw-px-2 tw-py-1 tw-text-[11px] tw-font-medium tw-bg-[#f3f2f1] tw-text-[#605e5d] tw-rounded tw-border">
            ID: GHN
        </span>
    </div>

    <div class="tw-px-8 tw-py-6">
        @php $ghn = $configs['ghn']->value ?? []; @endphp
        <form method="POST" action="{{ route('settings.updateGhn') }}" class="tw-space-y-6">
            @csrf @method('PATCH')

            <div class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-[#faf9f8] tw-rounded-md tw-border">
                <div>
                    <div class="tw-font-medium tw-text-sm">Trạng thái hoạt động</div>
                    <div class="tw-text-xs tw-text-[#605e5d]">Bật/Tắt dùng cấu hình GHN động từ hệ thống</div>
                </div>
                <x-switch name="is_active" value="1" :checked="old('is_active', $ghn['is_active'] ?? false)" />
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5">
                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="api_url">GHN_API_URL</x-label>
                    <x-input id="api_url" name="api_url" :value="old('api_url', $ghn['api_url'] ?? env('GHN_API_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api'))"
                        placeholder="https://dev-online-gateway.ghn.vn/shiip/public-api" />
                </div>

                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="shop_id">GHN_SHOP_ID</x-label>
                    <x-input id="shop_id" name="shop_id" :value="old('shop_id', $ghn['shop_id'] ?? env('GHN_SHOP_ID'))"
                        placeholder="200478" />
                </div>

                <div class="tw-col-span-full tw-flex tw-flex-col tw-gap-1">
                    <x-label for="api_token">GHN_API_TOKEN</x-label>
                    <x-input id="api_token" name="api_token" type="password" placeholder="••••••••"
                        :value="old('api_token')" helper="Chỉ nhập khi bạn muốn thay đổi API token mới." />
                </div>
            </div>

            <div class="tw-pt-6 tw-border-t tw-flex tw-justify-end tw-gap-3">
                @can('settings.update')
                    <button type="submit"
                        class="tw-px-4 tw-py-2 tw-text-sm tw-font-bold tw-text-white tw-bg-[#0078d4] tw-rounded hover:tw-bg-[#106ebe] tw-transition-colors">
                        Lưu cấu hình GHN
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>
