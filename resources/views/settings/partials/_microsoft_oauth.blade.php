<div class="tw-bg-white tw-rounded-md tw-shadow-sm tw-border tw-border-[#edebe9]">
    <div class="tw-px-6 tw-py-4 tw-border-b tw-flex tw-justify-between tw-items-center">
        <h5 class="tw-text-lg tw-font-semibold tw-text-[#323130] tw-mb-0">Microsoft OAuth Configuration</h5>
        <span class="tw-px-2 tw-py-1 tw-text-[11px] tw-font-medium tw-bg-[#f3f2f1] tw-text-[#605e5d] tw-rounded tw-border">
            ID: Microsoft
        </span>
    </div>

    <div class="tw-px-8 tw-py-6">
        @php $data = $configs['microsoft']->value ?? []; @endphp
        <form method="POST" action="{{ route('settings.updateOAuth', 'microsoft') }}" class="tw-space-y-6" novalidate>
            @csrf @method('PATCH')

            <div class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-[#faf9f8] tw-rounded-md tw-border">
                <div>
                    <div class="tw-font-medium tw-text-sm">Trạng thái hoạt động</div>
                    <div class="tw-text-xs tw-text-[#605e5d]">Bật/Tắt đăng nhập qua Microsoft OAuth</div>
                </div>
                <x-switch name="is_active" value="1" :checked="old('is_active', $data['is_active'] ?? false)" />
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5">
                
                <div class="tw-col-span-full">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label for="ms_client_id" class="is-required">Client ID (Application ID)</x-label>
                        <x-input id="ms_client_id" name="client_id" :value="old('client_id', $data['client_id'] ?? '')" required="true" />
                        <x-input-error :messages="$errors->get('client_id')"/>
                    </div>
                </div>

                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="ms_tenant" class="is-required">Tenant</x-label>
                    <x-input id="ms_tenant" name="tenant" :value="old('tenant', $data['tenant'] ?? 'common')" required="true"
                        helper="Mặc định là 'common' để hỗ trợ cả tài khoản cá nhân & doanh nghiệp." />
                    <x-input-error :messages="$errors->get('tenant')"/>
                </div>

                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="ms_redirect_uri" class="is-required">Redirect URI</x-label>
                    <x-input id="ms_redirect_uri" name="redirect_uri" :value="old('redirect_uri', $data['redirect_uri'] ?? '')" required="true"
                        helper="Copy URI này dán vào Azure / Entra ID Portal." />
                    <x-input-error :messages="$errors->get('redirect_uri')"/>
                </div>

                <div class="tw-col-span-full md:tw-col-span-1">
                    <div class="tw-flex tw-flex-col tw-gap-1">
                        <x-label for="ms_client_secret">Client Secret (Value)</x-label>
                        <x-input id="ms_client_secret" name="client_secret" type="password" placeholder="••••••••"
                            :value="old('client_secret')" helper="Chỉ nhập khi bạn muốn thay đổi Secret mới."/>
                        <x-input-error :messages="$errors->get('client_secret')"/>
                    </div>
                </div>

            </div>

            <div class="tw-pt-6 tw-border-t tw-flex tw-justify-end tw-gap-3">
                @can('settings.edit')
                    <button type="submit"
                        class="tw-px-4 tw-py-2 tw-text-sm tw-font-bold tw-text-white tw-bg-[#0078d4] tw-rounded hover:tw-bg-[#106ebe] tw-transition-colors">
                        Lưu cấu hình
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>
