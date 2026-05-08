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

    <form method="POST" action="{{ route('login') }}">
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

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Noti box close
                $(document).on('click', '.noti-box', function() {
                    $(this).fadeOut(300, function() {
                        $(this).addClass('tw-hidden');
                    });
                });
            });
        </script>
    @endpush
</x-guest-layout>
