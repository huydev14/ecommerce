@extends('layouts.main')

@section('page-header')
    <x-page-header :title="'Preview import #' . $batch->id" description="Kiểm tra dữ liệu trước khi xác nhận import sản phẩm." />
@endsection

@section('content')
    @php
        $statusClass = [
            'ready' => 'tw-bg-[#eff6fc] tw-text-[#0f6cbd]',
            'processing' => 'tw-bg-amber-50 tw-text-amber-700',
            'importing' => 'tw-bg-violet-50 tw-text-violet-700',
            'completed' => 'tw-bg-emerald-50 tw-text-emerald-700',
        ][$batch->status] ?? 'tw-bg-gray-100 tw-text-gray-700';
    @endphp

    <div class="tw-h-full tw-overflow-y-auto tw-px-6 tw-pb-6">
        @if (session('error'))
            <div class="tw-mb-4 tw-rounded tw-border tw-border-red-200 tw-bg-red-50 tw-px-4 tw-py-3 tw-text-sm tw-text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <section class="tw-bg-white tw-border tw-border-gray-200 tw-rounded tw-shadow-sm">
            <div class="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-center lg:tw-justify-between tw-gap-4 tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                <div>
                    <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                        <span class="tw-inline-flex tw-rounded tw-px-2.5 tw-py-1 tw-text-xs tw-font-semibold {{ $statusClass }}">
                            {{ ucfirst($batch->status) }}
                        </span>
                        <span class="tw-text-xs tw-text-gray-500">Tạo lúc {{ $batch->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <h3 class="tw-mt-3 tw-text-2xl tw-font-semibold tw-text-gray-950">Dữ liệu sản phẩm đã đọc</h3>
                    <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Chỉ các dòng hợp lệ sẽ được xử lý khi xác nhận.</p>
                </div>

                <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                    <a href="{{ route('product-imports.index') }}"
                        class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-700 hover:tw-bg-gray-50">
                        <i class="fas fa-arrow-left"></i>
                        Upload file khác
                    </a>

                    <form action="{{ route('product-imports.confirm', $batch->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded tw-bg-[#0f6cbd] tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-shadow-sm hover:tw-bg-[#115ea3] focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-[#0f6cbd] focus:tw-ring-offset-2">
                            <i class="fas fa-check"></i>
                            Xác nhận import
                        </button>
                    </form>
                </div>
            </div>

            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-border-b tw-border-gray-100">
                <div class="tw-px-6 tw-py-4 md:tw-border-r tw-border-gray-100">
                    <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">Tổng số dòng</p>
                    <p class="tw-mt-2 tw-text-2xl tw-font-semibold tw-text-gray-950">{{ number_format($batch->total_rows) }}</p>
                </div>
                <div class="tw-px-6 tw-py-4 md:tw-border-r tw-border-gray-100">
                    <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">Hợp lệ</p>
                    <p class="tw-mt-2 tw-text-2xl tw-font-semibold tw-text-emerald-700">{{ number_format($validRows) }}</p>
                </div>
                <div class="tw-px-6 tw-py-4">
                    <p class="tw-text-xs tw-font-medium tw-uppercase tw-text-gray-500">Có lỗi</p>
                    <p class="tw-mt-2 tw-text-2xl tw-font-semibold tw-text-red-700">{{ number_format($errorRows) }}</p>
                </div>
            </div>

            <div class="tw-overflow-x-auto">
                <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                    <thead class="tw-bg-gray-50">
                        <tr>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">Dòng</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">Tên sản phẩm</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">Giá</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">Danh mục</th>
                            <th class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">Trạng thái</th>
                            <th class="tw-min-w-[260px] tw-px-5 tw-py-3 tw-text-left tw-text-xs tw-font-semibold tw-uppercase tw-text-gray-500">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="tw-divide-y tw-divide-gray-100 tw-bg-white">
                        @forelse ($rows as $row)
                            <tr class="hover:tw-bg-gray-50">
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-font-medium tw-text-gray-900">#{{ $row->row_number }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ $row->data['name'] ?? '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ isset($row->data['price']) ? number_format((float) $row->data['price']) : '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3 tw-text-sm tw-text-gray-700">{{ $row->data['category_name'] ?? '-' }}</td>
                                <td class="tw-whitespace-nowrap tw-px-5 tw-py-3">
                                    @if ($row->status === 'valid')
                                        <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-emerald-50 tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-emerald-700">
                                            <i class="fas fa-circle-check"></i>
                                            Hợp lệ
                                        </span>
                                    @else
                                        <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-red-50 tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-red-700">
                                            <i class="fas fa-circle-exclamation"></i>
                                            Lỗi
                                        </span>
                                    @endif
                                </td>
                                <td class="tw-px-5 tw-py-3 tw-text-sm tw-text-gray-600">{{ $row->error_message ?: 'Sẵn sàng import' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="tw-px-5 tw-py-10 tw-text-center tw-text-sm tw-text-gray-500">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tw-border-t tw-border-gray-100 tw-px-5 tw-py-4">
                {{ $rows->links() }}
            </div>
        </section>
    </div>
@endsection
