<div class="tw-bg-white tw-rounded-md tw-shadow-sm tw-border tw-border-[#edebe9]">
    <div class="tw-px-6 tw-py-4 tw-border-b tw-flex tw-justify-between tw-items-center">
        <h5 class="tw-text-lg tw-font-semibold tw-text-[#323130] tw-mb-0">VNPAY Configuration</h5>
        <span
            class="tw-px-2 tw-py-1 tw-text-[11px] tw-font-medium tw-bg-[#f3f2f1] tw-text-[#605e5d] tw-rounded tw-border">
            ID: VNPAY
        </span>
    </div>

    <div class="tw-px-8 tw-py-6">
        @php $vnpay = $configs['vnpay']->value ?? []; @endphp
        <form method="POST" action="{{ route('settings.updateVnpay') }}" class="tw-space-y-6">
            @csrf @method('PATCH')

            <div class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-[#faf9f8] tw-rounded-md tw-border">
                <div>
                    <div class="tw-font-medium tw-text-sm">Trạng thái hoạt động</div>
                    <div class="tw-text-xs tw-text-[#605e5d]">Bật/Tắt dùng cấu hình VNPAY động từ hệ thống</div>
                </div>
                <x-switch name="is_active" value="1" :checked="old('is_active', $vnpay['is_active'] ?? false)" />
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5">
                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="tmn_code">VNP_TMNCODE</x-label>
                    <x-input id="tmn_code" name="tmn_code" :value="old('tmn_code', $vnpay['tmn_code'] ?? config('vnpay.tmn_code'))"
                        placeholder="VNPAY terminal code" />
                </div>

                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="hash_secret">VNP_HASHSECRET</x-label>
                    <x-input id="hash_secret" name="hash_secret" type="password" placeholder="••••••••"
                        :value="old('hash_secret')" helper="Chỉ nhập khi bạn muốn thay đổi Hash Secret mới." />
                </div>

                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="url">VNP_URL</x-label>
                    <x-input id="url" name="url" :value="old('url', $vnpay['url'] ?? config('vnpay.url'))"
                        placeholder="https://sandbox.vnpayment.vn/paymentv2/vpcpay.html" />
                </div>

                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="return_url">VNP_RETURN_URL</x-label>
                    <x-input id="return_url" name="return_url" :value="old('return_url', $vnpay['return_url'] ?? config('vnpay.return_url'))"
                        placeholder="{{ rtrim(config('app.url'), '/') }}/checkout/vnpay-return" />
                </div>
            </div>

            <div class="tw-pt-6 tw-border-t tw-flex tw-justify-end tw-gap-3">
                @can('settings.update')
                    <button type="submit"
                        class="tw-px-4 tw-py-2 tw-text-sm tw-font-bold tw-text-white tw-bg-[#0078d4] tw-rounded hover:tw-bg-[#106ebe] tw-transition-colors">
                        Lưu cấu hình VNPAY
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>
