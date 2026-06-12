@extends('layouts.main')

@section('page-header')
    <x-page-header title="Home" description="Dashboard & Analysis"/>
@endsection

@section('content')
    @php
        $orderTotal = $pendingOrders + $completedOrders;
        $completionRate = $orderTotal > 0 ? round(($completedOrders / $orderTotal) * 100) : 0;

        $widgets = [
            [
                'label' => 'Doanh thu',
                'value' => number_format($totalRevenue, 0, ',', '.') . ' đ',
                'meta' => 'Đơn hàng hoàn tất',
                'icon' => 'fas fa-sack-dollar',
                'accent' => '#107c10',
                'url' => route('orders.index'),
            ],
            [
                'label' => 'Đơn chờ xử lý',
                'value' => number_format($pendingOrders),
                'meta' => 'Cần theo dõi',
                'icon' => 'fas fa-clock',
                'accent' => '#ffaa44',
                'url' => route('orders.index'),
            ],
            [
                'label' => 'Đơn hoàn tất',
                'value' => number_format($completedOrders),
                'meta' => $completionRate . '% tỷ lệ hoàn tất',
                'icon' => 'fas fa-circle-check',
                'accent' => '#0f6cbd',
                'url' => route('orders.index'),
            ],
            [
                'label' => 'Đơn tháng này',
                'value' => number_format($thisMonthOrders),
                'meta' => now()->format('m/Y'),
                'icon' => 'fas fa-calendar-days',
                'accent' => '#8764b8',
                'url' => route('orders.index'),
            ],
            [
                'label' => 'Sản phẩm đang bán',
                'value' => number_format($activeProducts),
                'meta' => 'Trạng thái published',
                'icon' => 'fas fa-box-open',
                'accent' => '#008575',
                'url' => route('products.index'),
            ],
            [
                'label' => 'Người dùng',
                'value' => number_format($totalUsers),
                'meta' => 'Tài khoản hệ thống',
                'icon' => 'fas fa-users',
                'accent' => '#d13438',
                'url' => route('users.index'),
            ],
        ];
    @endphp

    <div class="dashboard-widget-grid">
        @foreach ($widgets as $widget)
            <a href="{{ $widget['url'] }}" class="dashboard-widget" style="--widget-accent: {{ $widget['accent'] }}">
                <span class="dashboard-widget__bar"></span>

                <span class="dashboard-widget__body">
                    <span class="dashboard-widget__icon">
                        <i class="{{ $widget['icon'] }}"></i>
                    </span>

                    <span class="dashboard-widget__copy">
                        <span class="dashboard-widget__label">{{ $widget['label'] }}</span>
                        <span class="dashboard-widget__value">{{ $widget['value'] }}</span>
                        <span class="dashboard-widget__meta">{{ $widget['meta'] }}</span>
                    </span>
                </span>

                <span class="dashboard-widget__go" aria-hidden="true">
                    <i class="fas fa-chevron-right"></i>
                </span>
            </a>
        @endforeach
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-widget-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            padding: 0 24px 24px;
        }

        .dashboard-widget {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 112px;
            overflow: hidden;
            color: #242424;
            background: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            box-shadow: 0 1.6px 3.6px rgba(0, 0, 0, 0.13), 0 0.3px 0.9px rgba(0, 0, 0, 0.11);
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            text-decoration: none;
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .dashboard-widget:hover {
            color: #242424;
            border-color: #c7e0f4;
            box-shadow: 0 3.2px 7.2px rgba(0, 0, 0, 0.13), 0 0.6px 1.8px rgba(0, 0, 0, 0.11);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .dashboard-widget:focus-visible {
            outline: 2px solid #0f6cbd;
            outline-offset: 2px;
        }

        .dashboard-widget__bar {
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--widget-accent);
        }

        .dashboard-widget__body {
            display: flex;
            align-items: center;
            min-width: 0;
            gap: 14px;
            padding: 18px 18px 18px 20px;
        }

        .dashboard-widget__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            color: var(--widget-accent);
            background: color-mix(in srgb, var(--widget-accent) 12%, #ffffff);
            border-radius: 4px;
            font-size: 16px;
        }

        .dashboard-widget__copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 3px;
        }

        .dashboard-widget__label {
            color: #616161;
            font-size: 13px;
            font-weight: 600;
            line-height: 18px;
        }

        .dashboard-widget__value {
            color: #242424;
            font-size: 26px;
            font-weight: 700;
            line-height: 32px;
        }

        .dashboard-widget__meta {
            overflow: hidden;
            color: #707070;
            font-size: 12px;
            line-height: 16px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-widget__go {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            margin-right: 12px;
            color: #707070;
            border-radius: 4px;
            font-size: 12px;
            transition: background-color 160ms ease, color 160ms ease;
        }

        .dashboard-widget:hover .dashboard-widget__go {
            color: #0f6cbd;
            background: #eff6fc;
        }

        @media (max-width: 1199.98px) {
            .dashboard-widget-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-widget-grid {
                grid-template-columns: 1fr;
                padding: 0 16px 16px;
            }

            .dashboard-widget {
                min-height: 96px;
            }

            .dashboard-widget__value {
                font-size: 22px;
                line-height: 28px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            @include('partials.fluent-session-toasts')
        });
    </script>
@endpush
