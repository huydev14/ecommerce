<form id="form-edit-user" method="POST" action="{{ route('users.update', $user->id) }}"
    class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0" novalidate>
    @csrf
    @method('PUT')
    <div class="tw-flex-1 tw-min-h-0 tw-overflow-y-auto tw-p-6 tw-flex tw-flex-col tw-gap-5">

        <div class="tw-space-y-4">
            <h3 class="tw-text-xs tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wider">
                Thông tin cá nhân
            </h3>
            {{-- Name --}}
            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="name" class="is-required">Họ và tên</x-label>
                <x-input :value="$user->name" id="name" name="name" icon="far fa-user"
                    placeholder="Nhập họ và tên" required />
            </div>
            {{-- Email --}}
            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="email" class="is-required">Email</x-label>
                <x-input :value="$user->email" type="email" id="email" name="email"
                    icon="far fa-envelope" placeholder="email@congty.com" required />
            </div>
            {{-- Password --}}
            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="password">Mật khẩu</x-label>
                <x-input type="password" id="password" name="password" icon="fas fa-lock"
                    placeholder="Bỏ trống nếu không đổi" />
            </div>

            <div class="tw-grid tw-grid-cols-2 tw-gap-4">
                {{-- Phone number --}}
                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="phone">Số điện thoại</x-label>
                    <x-input :value="$user->phone" type="tel" id="phone" name="phone"
                        icon="fas fa-phone-alt" placeholder="09xx..." />
                </div>
                {{-- Birthday --}}
                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="birthday">Ngày sinh</x-label>
                    <x-input :value="$user->birthday" type="date" id="birthday" name="birthday" />
                </div>
            </div>

            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="address">Địa chỉ</x-label>
                <x-input :value="$user->address" id="address" name="address" icon="fas fa-map-marker-alt"
                    placeholder="Nhập địa chỉ hiện tại" />
            </div>

            <div class="tw-flex tw-flex-col tw-gap-1">
                {{-- Gender --}}
                <label class="fluent-label is-required">Giới tính</label>
                <div class="tw-flex tw-gap-4 tw-pt-1">
                    <label class="tw-flex tw-items-center tw-gap-2 tw-cursor-pointer group">
                        <input type="radio" name="gender" value="0" class="tw-peer tw-sr-only" checked>
                        <div
                            class="tw-w-4 tw-h-4 tw-rounded-full tw-border tw-border-gray-500 peer-checked:tw-border-[#0063B1] peer-checked:tw-border-[5px] tw-transition-all">
                        </div>
                        <span class="tw-text-sm tw-text-gray-800">Nam</span>
                    </label>
                    <label class="tw-flex tw-items-center tw-gap-2 tw-cursor-pointer group">
                        <input type="radio" name="gender" value="1" class="tw-peer tw-sr-only">
                        <div
                            class="tw-w-4 tw-h-4 tw-rounded-full tw-border tw-border-gray-500 peer-checked:tw-border-[#0063B1] peer-checked:tw-border-[5px] tw-transition-all">
                        </div>
                        <span class="tw-text-sm tw-text-gray-800">Nữ</span>
                    </label>
                </div>
            </div>
        </div>

        <hr class="tw-border-gray-200">

        <div class="tw-space-y-4">
            <h3 class="tw-text-xs tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wider">
                Thông tin công việc
            </h3>
            <x-select title="Phòng ban" id="edit-department" name="department_id" required>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($user->department_id == $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </x-select>

            <x-select title="Đội nhóm" id="edit-team" name="team_id">
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}" @selected($user->team_id == $team->id)>
                        {{ $team->name }}
                    </option>
                @endforeach
            </x-select>

            @php
                $currentRoleId = $user->roles->first()->id ?? null;
            @endphp
            <x-select title="Loại tài khoản" id="edit-user-role" name="role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected($currentRoleId == $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </x-select>

            <div class="tw-grid tw-grid-cols-2 tw-gap-4"></div>

            <div class="tw-grid tw-grid-cols-2 tw-gap-4">
                <div class="tw-flex tw-flex-col tw-gap-1">
                    <x-label for="start_date">Ngày bắt đầu</x-label>
                    <x-input :value="$user->start_date" type="date" id="start_date" name="start_date" />
                </div>

                <div class="tw-flex tw-flex-col tw-gap-1">
                    <label class="fluent-label is-required">Hình thức</label>
                    <div class="tw-flex tw-gap-4 tw-pt-1">
                        <label class="tw-flex tw-items-center tw-gap-2 tw-cursor-pointer">
                            <input type="radio" name="employment_type" value="0" class="tw-peer tw-sr-only"
                                checked>
                            <div
                                class="tw-w-4 tw-h-4 tw-rounded-full tw-border tw-border-gray-500 peer-checked:tw-border-[#0063B1] peer-checked:tw-border-[5px] tw-transition-all">
                            </div>
                            <span class="tw-text-sm tw-text-gray-800">Full-time</span>
                        </label>
                        <label class="tw-flex tw-items-center tw-gap-2 tw-cursor-pointer">
                            <input type="radio" name="employment_type" value="1" class="tw-peer tw-sr-only">
                            <div
                                class="tw-w-4 tw-h-4 tw-rounded-full tw-border tw-border-gray-500 peer-checked:tw-border-[#0063B1] peer-checked:tw-border-[5px] tw-transition-all">
                            </div>
                            <span class="tw-text-sm tw-text-gray-800">Part-time</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        class="tw-flex tw-items-center tw-justify-end tw-gap-3 tw-px-6 tw-py-4 tw-border-t tw-border-gray-200 tw-bg-gray-50">

        <button type="button" class="close-slideover fluent-btn-cancel">Hủy bỏ</button>
        <button type="submit" class="fluent-btn-submit">Lưu thay đổi</button>
    </div>

</form>
