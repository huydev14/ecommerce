<nav class="navbar navbar-expand topbar">
    <div class="brand-area">
        <button type="button" class="nav-link icon-btn" data-widget="pushmenu" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/admin" class="brand-link">
            <span class="brand-mark" aria-label="{{ config('app.name') }}">
                <span class="brand-name">{{ config('app.name') }}</span>
                <span class="brand-smile"></span>
            </span>
            <span class="brand-module">{{ __('header.field_service') }}</span>
        </a>
    </div>

    <form class="search-wrap tw-m-0" method="GET" action="{{ route('audit-logs.index') }}">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="admin-global-search" name="q" class="form-control search-input tw-pr-16"
            placeholder="{{ __('header.search_placeholder') }}"
            value="{{ request()->routeIs('audit-logs.*') ? request('q') : '' }}" aria-label="Search">
        <button type="button"
            class="tw-absolute tw-right-1.5 tw-top-1/2 -tw-translate-y-1/2 tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-1.5 tw-py-0.5 tw-text-[10px] tw-font-semibold tw-leading-none tw-text-gray-500"
            title="{{ __('header.search_shortcut') }}" aria-label="{{ __('header.search_shortcut') }}"
            data-search-shortcut>
            Ctrl K
        </button>
    </form>


    <ul class="navbar-nav topbar-actions">
         <li class="nav-item ">
            <a href="/" class="nav-link icon-btn tw-flex tw-gap-2 !tw-px-1">
               <x-icon-building-shop/> Back to shop
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ url('horizon') }}" class="nav-link icon-btn tw-flex tw-items-center tw-gap-1 tw-leading-none !tw-px-2" target="_blank" title="Horizon Monitoring">
                <div><i class="fas fa-chart-line"></i></div>
                <span>Queue</span>
            </a>
        </li>
       
        <li class="nav-item ">
            <button type="button" class="nav-link icon-btn " data-widget="fullscreen" aria-label="Fullscreen">
               <x-icon-arrow-expand/>
            </button>
        </li>

        <li class="nav-item">
            <a href="#" data-bs-toggle="dropdown" class="nav-link icon-btn !tw-px-1 !tw-py-1">
                <div class="tw-flex tw-flex-row ">
                    @if (App::getLocale() == 'vi')
                        <i class="fi fi-vn"></i>
                    @else
                        <i class="fi fi-gb"></i>
                    @endif
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="{{ route('lang.switch', 'vi') }}"
                    class="dropdown-item {{ App::getLocale() == 'vi' ? 'active !tw' : '' }}">
                    <i class="fi fi-vn"></i> {{ __('header.vi') }}
                </a>
                <a href="{{ route('lang.switch', 'en') }}"
                    class="dropdown-item {{ App::getLocale() == 'en' ? 'active' : '' }}">
                    <i class="fi fi-gb"></i> {{ __('header.en') }}
                </a>
            </div>
        </li>

        <li class="nav-item">
            <a data-bs-toggle="dropdown" href="#" class="nav-link profile-link icon-btn">
                <div class="tw-flex tw-flex-col tw-items-end">
                    <span>{{ auth()->user()->name }}</span>
                    <span class="tw-text-xs tw-opacity-75">{{ auth()->user()->email }}</span>
                </div>
                <img src="{{ asset('adminlte/dist/img/avatar-default.jpg') }}" class="img-circle" alt="User">
            </a>

            <div class="dropdown-menu dropdown-menu-right">
                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                    <i class="fas fa-user mr-2"></i> {{ __('header.profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> {{ __('actions.log_out') }}
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const globalSearch = document.getElementById('admin-global-search');
            const shortcutHint = document.querySelector('[data-search-shortcut]');
            const isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform);

            if (!globalSearch || !shortcutHint) {
                return;
            }

            const focusGlobalSearch = function() {
                globalSearch.focus();
                globalSearch.select();
            };

            shortcutHint.textContent = isMac ? '⌘ K' : 'Ctrl K';
            shortcutHint.addEventListener('click', focusGlobalSearch);

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    focusGlobalSearch();
                }
            });
        });
    </script>
@endpush
