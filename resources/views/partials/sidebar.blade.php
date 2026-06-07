<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <x-icon-home class="nav-icon" />
                <p>{{ __('header.home') }}</p>
            </a>
        </li>

        <li class="nav-header">KINH DOANH</li>

        <li class="nav-item">
            <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <x-icon-shopping class="nav-icon" />
                <p>{{ __('order.management_title') }}</p>
            </a>
        </li>

        <li class="nav-header">SẢN PHẨM</li>

        <li class="nav-item">
            <a href="{{ route('products.index') }}"
                class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <x-icon-box class="nav-icon" />
                <p>{{ __('product.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('product-variants.index') }}"
                class="nav-link {{ request()->routeIs('product-variants.*') ? 'active' : '' }}">
                <x-icon-layers class="nav-icon" />
                <p>{{ __('product.variant_management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('categories.index') }}"
                class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <x-icon-category class="nav-icon" />
                <p>{{ __('category.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('brands.index') }}"
                class="nav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                <x-icon-tag class="nav-icon" />
                <p>{{ __('brand.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('units.index') }}" class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                <x-icon-scale class="nav-icon" />
                <p>{{ __('unit.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('taxes.index') }}" class="nav-link {{ request()->routeIs('taxes.*') ? 'active' : '' }}">
                <x-icon-receipt class="nav-icon" />
                <p>{{ __('tax.management_title') }}</p>
            </a>
        </li>

        {{-- Vận hành --}}
        <li class="nav-header">VẬN HÀNH</li>

        <li class="nav-item">
            <a href="{{ route('product-imports.index') }}"
                class="nav-link {{ request()->routeIs('product-imports.*') ? 'active' : '' }}">
                <x-icon-upload class="nav-icon" />
                <p>Import sản phẩm</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('warehouses.index') }}"
                class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                <x-icon-building class="nav-icon" />
                <p>{{ __('warehouse.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stocks.index') }}"
                class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                <x-icon-archive class="nav-icon" />
                <p>{{ __('stock.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('stock-movements.index') }}"
                class="nav-link {{ request()->routeIs('stock-movements.*') ? 'active' : '' }}">
                <x-icon-history class="nav-icon" />
                <p>{{ __('stock_movement.management_title') }}</p>
            </a>
        </li>

        <li class="nav-header">CUSTOMERS</li>

        <li class="nav-item">
            <a href="{{ route('customer-addresses.index') }}"
                class="nav-link {{ request()->routeIs('customer-addresses.*') ? 'active' : '' }}">
                <x-icon-people-building class="nav-icon" />
                <p>{{ __('customer_address.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('banners.index') }}"
                class="nav-link {{ request()->routeIs('banners.*') ? 'active' : '' }}">
                <x-icon-megaphone class="nav-icon" />
                <p>Quản lý Banner</p>
            </a>
        </li>

        <li class="nav-header">HỆ THỐNG</li>

        <li class="nav-item">
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <x-icon-people-setting class="nav-icon" />
                <p>{{ __('user.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <x-icon-shield class="nav-icon" />
                <p>{{ __('role.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('audit-logs.index') }}"
                class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                <x-icon-clipboard-list class="nav-icon" />
                <p>{{ __('audit.management_title') }}</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('settings.index') . '?tab=mail' }}"
                class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <x-icon-setting class="nav-icon" />
                <p>{{ __('header.settings') }}</p>
            </a>
        </li>
    </ul>
</nav>
