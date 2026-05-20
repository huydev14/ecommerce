<!-- Sidebar Menu -->
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item {{ request()->routeIs('dashboard') ? 'menu-open' : '' }}">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <x-icon-home class="nav-icon" />
                <p>{{ __('header.home') }}</p>
            </a>
        </li>

        {{-- Bán hàng --}}
        <li class="nav-item menu-open {{ request()->routeIs('brands.*') || request()->routeIs('categories.*') || request()->routeIs('products.*') || request()->routeIs('product-variants.*') || request()->routeIs('units.*') || request()->routeIs('taxes.*') || request()->routeIs('product-imports.*') ? 'menu-is-opening ' : '' }}">
            <a href="#" class="nav-link">
                <x-icon-shopping class="nav-icon" />
                <p> {{ __('header.sales') }}
                    <x-icon-chevron-left class="right" />
                </p>
            </a>
            <ul class="nav nav-treeview ">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('brands.index') ? 'active' : '' }}" href="{{ route('brands.index') }}">
                        <p>{{ __('brand.management_title') }}</p>
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.index') ? 'active' : '' }}">
                        <p>{{ __('category.management_title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">
                        <p>{{ __('product.management_title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('product-variants.index') }}" class="nav-link {{ request()->routeIs('product-variants.index') ? 'active' : '' }}">
                        <p>{{ __('product.variant_management_title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('units.index') }}" class="nav-link {{ request()->routeIs('units.index') ? 'active' : '' }}">
                        <p>{{ __('unit.management_title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('taxes.index') }}" class="nav-link {{ request()->routeIs('taxes.index') ? 'active' : '' }}">
                        <p>{{ __('tax.management_title') }}</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('product-imports.index') }}" class="nav-link {{ request()->routeIs('product-imports.*') ? 'active' : '' }}">
                        <p>Import sản phẩm</p>
                    </a>
                </li>
                {{-- ---<li class="nav-item">
                    <a href="pages/UI/icons.html" class="nav-link">
                        <p>Tồn kho</p>
                    </a>
                </li> --}}
            </ul>
        </li>

        {{-- Account --}}
        <li
            class="nav-item menu-open {{ request()->routeIs('users.*') && request()->routeIs('roles.*') ? 'menu-is-opening ' : '' }}">
            <a href="#" class="nav-link">
                <x-icon-people-setting class="nav-icon" />
                <p>{{ __('header.accounts') }}
                    <x-icon-chevron-left class="right" />
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item ">
                    <a href="{{ route('users.index') }}"
                        class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <p>{{ __('user.management_title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('roles.index') }}"
                        class="nav-link {{ request()->routeIs('roles.index') ? 'active' : '' }}">
                        <p>{{ __('role.management_title') }}</p>
                    </a>
                </li>
            </ul>
        </li>

         {{-- System configuration --}}
        <li class="nav-item menu-open {{ request()->routeIs('audit-logs.*') || request()->routeIs('settings.*') ? 'menu-is-opening ' : '' }}">
            <a href="#" class="nav-link">
                <x-icon-setting class="nav-icon" />
                <p>{{ __('header.system') }}
                    <x-icon-chevron-left class="right" />
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="{{ route('audit-logs.index') }}"
                        class="nav-link {{ request()->routeIs('audit-logs.index') ? 'active' : '' }}">
                        <p>{{ __('audit.management_title') }}</p>
                    </a>
                </li>
            </ul>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="{{ route('settings.index') . '?tab=mail'}}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <p>{{ __('header.settings') }}</p>
                    </a>
                </li>
            </ul>
        </li>

        {{-- <li class="nav-item">
            <a href="#" class="nav-link">
                <x-icon-wallet class="nav-icon" />
                <p>Tài chính
                    <x-icon-chevron-left class="right" />
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="pages/layout/top-nav.html" class="nav-link">
                        <p>Bảng kế toán</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pages/layout/top-nav-sidebar.html" class="nav-link">
                        <p>Bảng lương</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pages/layout/boxed.html" class="nav-link">
                        <p>Chi tiêu</p>
                    </a>
                </li>
            </ul>
        </li> --}}

        {{-- Nhập hàng --}}
        {{-- <li class="nav-item">
            <a href="#" class="nav-link">
                <x-icon-truck class="nav-icon" />
                <p>Nhập hàng
                    <x-icon-chevron-left class="right" />
                </p>
            </a>

            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="pages/charts/chartjs.html" class="nav-link">
                        <p>Xếp lô</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pages/charts/flot.html" class="nav-link">
                        <p>Quản lý đơn hàng</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pages/charts/inline.html" class="nav-link">
                        <p>Khách hàng</p>
                    </a>
                </li>
            </ul>
        </li> --}}



        {{-- Hành chính nhân sự --}}
        {{-- <li class="nav-item">
            <a href="#" class="nav-link">
                <x-icon-people-building class="nav-icon" />
                <p>HCNS
                    <x-icon-chevron-left class="right" />
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="pages/forms/general.html" class="nav-link">
                        <p>Tuyển dụng</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pages/forms/advanced.html" class="nav-link">
                        <p>Cơ sở vật chất</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pages/forms/editors.html" class="nav-link">
                        <p>Chấm công</p>
                    </a>
                </li>
            </ul>
        </li> --}}
    </ul>
</nav>
