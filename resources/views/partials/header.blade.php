<nav class="navbar navbar-expand topbar">
    <div class="brand-area">
        <button type="button" class="nav-link icon-btn" data-widget="pushmenu" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/" class="brand-link">
            <span class="brand-mark" aria-label="{{ config('app.name') }}">
                <span class="brand-name">{{ config('app.name') }}</span>
                <span class="brand-smile"></span>
            </span>
            <span class="brand-module">{{ __('header.field_service') }}</span>
        </a>
    </div>

    <div class="search-wrap">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="form-control search-input" placeholder="{{ __('header.search_placeholder') }}"
            aria-label="Search">
    </div>


    <ul class="navbar-nav topbar-actions">
        <li class="nav-item">
            <a href="{{ url('horizon') }}" class="nav-link icon-btn tw-flex tw-items-center tw-gap-1 tw-leading-none" target="_blank" title="Horizon Monitoring">
                <div><i class="fas fa-chart-line"></i></div>
                <span>Queue Monitoring</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/" class="nav-link icon-btn">
                <i class="fas fa-store"></i>
            </a>
        </li>

        <li class="nav-item">
            <button type="button" class="nav-link icon-btn" data-widget="fullscreen" aria-label="Fullscreen">
                <i class="fas fa-expand-arrows-alt"></i>
            </button>
        </li>

        <li class="nav-item">
            <a href="#" data-bs-toggle="dropdown" class="nav-link icon-btn">
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
