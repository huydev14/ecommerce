<div class="tw-bg-white tw-rounded-md tw-shadow-sm tw-border tw-border-[#edebe9]">
    <div class="tw-p-6 tw-border-b tw-flex tw-justify-between tw-items-center">
        <h5 class="tw-text-lg tw-font-semibold tw-text-[#323130] tw-mb-0">Google OAuth Configuration</h5>
        <span class="tw-px-2 tw-py-1 tw-text-[11px] tw-font-medium tw-bg-[#f3f2f1] tw-text-[#605e5d] tw-rounded tw-border">
            ID: Google
        </span>
    </div>

    <div class="tw-p-8">
        @php $data = $configs['google']['value'] ?? []; @endphp
        <form method="POST" action="{{ route('settings.updateOAuth', 'google') }}" class="tw-space-y-6">
            @csrf @method('PATCH')

            <div class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-[#faf9f8] tw-rounded-md tw-border">
                <div>
                    <div class="tw-font-medium tw-text-sm">Trạng thái hoạt động</div>
                    <div class="tw-text-xs tw-text-[#605e5d]">Bật/Tắt đăng nhập qua Google OAuth</div>
                </div>
                <x-switch name="is_active" value="1"
                    :checked="old('is_active', $data['is_active'] ?? false)" />
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5">
                <x-input label="Client ID" name="client_id"
                    :value="old('client_id', $data['client_id'] ?? '')" required="true" />


                <x-input label="Client Secret" name="client_secret" type="password" placeholder="••••••••"
                    :value="old('client_secret')" required="true" />

                <div class="tw-col-span-full">
                    <x-input label="Redirect URI" name="redirect_uri"
                        :value="old('redirect_uri', $data['redirect_uri'] ?? '')"
                        helper="Copy URI này dán vào Google OAuth Console." />
                </div>
            </div>

            <div class="tw-pt-6 tw-border-t tw-flex tw-justify-end tw-gap-3">
                <button type="submit"
                    class="tw-px-4 tw-py-2 tw-text-sm tw-font-bold tw-text-white tw-bg-[#0078d4] tw-rounded hover:tw-bg-[#106ebe] tw-transition-colors">
                    Lưu cấu hình
                </button>
            </div>
        </form>
    </div>
</div>
