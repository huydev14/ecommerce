<div class="tw-bg-white tw-rounded-md tw-shadow-sm tw-border tw-border-[#edebe9]">
    <div class="tw-px-6 tw-py-4 tw-border-b tw-flex tw-justify-between tw-items-center">
        <h5 class="tw-text-lg tw-font-semibold tw-mb-0">SMTP Configuration</h5>
    </div>
    <div class="tw-px-8">
        <form method="POST" action="{{ route('settings.updateMail') }}" class="tw-space-y-6">
            @csrf @method('PATCH')
            @php $mail = $configs['smtp']->value ?? []; @endphp

            <div class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-[#faf9f8] tw-rounded-md tw-border">
                <div>
                    <div class="tw-font-medium tw-text-sm">Trạng thái hoạt động</div>
                    <div class="tw-text-xs tw-text-[#605e5d]">Bật/Tắt gửi mail hệ thống</div>
                </div>
                <x-switch name="is_active" value="1"
                    :checked="old('is_active', $mail['is_active'] ?? false)" />
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5">
                <x-input label="Mail Host" name="host" :value="old('host', $mail['host'] ?? '')" placeholder="smtp.gmail.com" />
                <x-input label="Mail Port" name="port" :value="old('port', $mail['port'] ?? '')" placeholder="587" />
                <x-input label="Username" name="username" :value="old('username', $mail['username'] ?? '')" />
                <x-input label="Password" name="password" type="password" placeholder="••••••••" :value="old('password')" />
                
                <x-input label="Gửi từ địa chỉ (Email)" name="from_address" :value="old('from_address', $mail['from_address'] ?? '')" placeholder="no-reply@example.com" />
                <x-input label="Tên người gửi (Name)" name="from_name" :value="old('from_name', $mail['from_name'] ?? '')" placeholder="System Notification" />

                <div class="tw-col-span-full">
                    <x-label id="encryption" label="Encryption" />
                    <select id="encryption" name="encryption" class="form-select tw-w-full">
                        <option value="">None</option>
                        <option value="tls" {{ old('encryption', $mail['encryption'] ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ old('encryption', $mail['encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>
            </div>

            <div class="tw-pt-6 tw-border-t tw-flex tw-justify-end">
                <button type="submit"
                    class="tw-px-4 tw-py-2 tw-text-sm tw-font-bold tw-text-white tw-bg-[#0078d4] tw-rounded">
                    Lưu cấu hình Mail
                </button>
            </div>
        </form>
    </div>
</div>
