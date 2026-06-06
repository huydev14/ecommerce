<x-guest-layout>
    @if (session('status'))
        <div class="noti-box tw-bg-white tw-border-l-4 tw-border-blue-500 tw-shadow-sm tw-p-4 tw-rounded-r-md tw-mb-6 tw-flex tw-items-start tw-justify-between tw-cursor-pointer hover:tw-bg-gray-50 tw-transition-all tw-duration-200"
            role="alert">
            <div class="tw-flex tw-items-center">
                <svg class="tw-w-5 tw-h-5 tw-text-blue-500 tw-mr-3 tw-flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="tw-font-medium tw-text-gray-700 tw-text-sm">{{ session('status') }}</p>
            </div>
            <button type="button" class="tw-text-gray-400 hover:tw-text-gray-600 tw-transition-colors tw-ml-4"
                aria-label="Close">
                <svg class="tw-w-4 tw-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>
    @endif

    <form id="admin-login-form" method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="tw-font-semibold tw-text-gray-700" />
            <x-text-input id="email"
                class="tw-block tw-mt-1 tw-w-full focus:tw-border-blue-500 focus:tw-ring-blue-500" type="email"
                name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="tw-mt-2" />
        </div>

        <div class="tw-mt-4">
            <x-input-label for="password" :value="__('Password')" class="tw-font-semibold tw-text-gray-700" />
            <x-text-input id="password"
                class="tw-block tw-mt-1 tw-w-full focus:tw-border-blue-500 focus:tw-ring-blue-500" type="password"
                name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="tw-mt-2" />
        </div>

        <div class="tw-block tw-mt-4">
            <label for="remember_me" class="tw-inline-flex tw-items-center">
                <input id="remember_me" type="checkbox"
                    class="tw-rounded tw-border-gray-300 tw-text-blue-600 tw-shadow-sm focus:tw-ring-blue-500"
                    name="remember">
                <span class="tw-ms-2 tw-text-sm tw-text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="tw-flex tw-items-center tw-justify-between">
            @if (Route::has('password.request'))
                <a class="tw-underline tw-text-sm tw-text-gray-600 hover:tw-text-blue-600 tw-transition-colors tw-rounded-md focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-w-full">
                <div
                    class="tw-flex tw-p-2 tw-items-center tw-gap-2 tw-text-sm tw-transition tw-duration-100 hover:tw-bg-gray-100 tw-rounded-md">
                    @if (App::getLocale() == 'vi')
                        <a href="{{ route('lang.switch', 'en') }}"
                            class="tw-flex tw-items-center tw-text-gray-600 hover:tw-text-gray-900">
                            <span class="fi fi-gb tw-mr-1"></span> EN
                        </a>
                    @else
                        <a href="{{ route('lang.switch', 'vi') }}"
                            class="tw-flex tw-items-center tw-text-gray-600 hover:tw-text-gray-900">
                            <span class="fi fi-vn tw-mr-1"></span> VI
                        </a>
                    @endif
                </div>

                <x-primary-button
                    class="tw-bg-blue-600 hover:tw-bg-blue-700 focus:tw-bg-blue-700 active:tw-bg-blue-800">
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </div>

        <div class="tw-mt-3 tw-flex tw-items-center tw-justify-center">
            <div class="tw-border-t tw-border-gray-200 tw-flex-grow"></div>
            <span class="tw-px-4 tw-text-xs tw-text-gray-500 tw-bg-white">Hoặc đăng nhập với</span>
            <div class="tw-border-t tw-border-gray-200 tw-flex-grow"></div>
        </div>

        <div class="tw-mt-3">
            <a href="{{ route('oauth.microsoft.redirect') }}"
                class="tw-w-full tw-flex tw-items-center tw-justify-center tw-px-4 tw-py-2 tw-bg-white tw-border tw-border-gray-300 tw-rounded-md tw-shadow-sm hover:tw-bg-gray-50 hover:tw-border-gray-400 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-all tw-duration-200">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft Logo"
                    class="tw-w-5 tw-h-5 tw-mr-3" />
                <span class="tw-text-sm tw-font-semibold tw-text-[#5E5E5E]">Microsoft</span>
            </a>
        </div>
    </form>

    <div class="tw-mt-6 tw-rounded-lg tw-border tw-border-blue-200 tw-bg-blue-50 tw-p-4">
        <div class="tw-mb-2 tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-bold tw-text-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="tw-h-[18px] tw-w-[18px]" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <polyline points="17 11 19 13 23 9"></polyline>
            </svg>
            <span>Welcome HR!</span>
        </div>

        <p class="tw-mb-3 tw-text-xs tw-leading-5 tw-text-gray-600">
            Đăng nhập nhanh bằng tài khoản Demo cho HR.
        </p>

        <button type="button" id="admin-hr-demo-login"
            class="tw-flex tw-w-full tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-transparent tw-bg-blue-600 tw-px-3 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-shadow-sm tw-transition-all tw-duration-200 hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2 active:tw-bg-blue-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="tw-h-4 tw-w-4" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                <polyline points="10 17 15 12 10 7"></polyline>
                <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
            <span>Tự động đăng nhập</span>
        </button>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.noti-box').forEach(function(notiBox) {
                    notiBox.addEventListener('click', function() {
                        notiBox.classList.add('tw-hidden');
                    });
                });

                document.getElementById('admin-hr-demo-login')?.addEventListener('click', function() {
                    document.getElementById('email').value = 'hr.demo@gmail.com';
                    document.getElementById('password').value = 'hrdemo';
                    document.getElementById('admin-login-form').submit();
                });
            });
        </script>
    @endpush
</x-guest-layout>
