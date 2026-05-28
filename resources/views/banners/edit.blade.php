@php
    $currentImage = $banner->image_url
        ? (preg_match('/^https?:\/\//i', $banner->image_url) || str_starts_with($banner->image_url, '/')
            ? $banner->image_url
            : asset('storage/' . $banner->image_url))
        : '';
@endphp

<form id="form-edit-banner" action="{{ route('banners.update', $banner->id) }}" method="POST" enctype="multipart/form-data"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf
    @method('PUT')

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">Cập nhật banner</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">Chỉnh sửa thông tin banner và lưu thay đổi.</p>
        </div>

        <div>
            <button type="submit" id="submit-edit-banner"
                class="tw-bg-[#0078D4] tw-border tw-border-transparent tw-px-4 tw-py-1.5 tw-text-sm tw-font-medium tw-text-white hover:tw-bg-[#106ebe] tw-transition-colors tw-rounded-sm shadow-sm">
                Lưu thay đổi
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div>
            <label for="title" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">Tiêu đề</label>
            <input type="text" name="title" id="title" value="{{ $banner->title }}"
                placeholder="Tiêu đề banner"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div>
            <label for="image_url" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">Ảnh
                banner</label>
            <div
                class="tw-flex tw-items-center tw-gap-4 tw-p-3 tw-border tw-border-dashed tw-border-gray-300 tw-rounded-md tw-bg-gray-50 hover:tw-bg-gray-100 tw-transition-colors">
                <div
                    class="tw-w-36 tw-h-20 tw-shrink-0 tw-rounded tw-border tw-border-gray-200 tw-bg-white tw-flex tw-items-center tw-justify-center tw-overflow-hidden">
                    <img id="banner-image-preview" src="{{ $currentImage }}" alt="Xem trước banner"
                        class="tw-w-full tw-h-full tw-object-cover {{ $currentImage ? '' : 'tw-hidden' }}">
                    <i id="banner-image-placeholder"
                        class="fas fa-image tw-text-gray-300 tw-text-xl {{ $currentImage ? 'tw-hidden' : '' }}"></i>
                </div>
                <div class="tw-flex-1">
                    <input type="file" name="image_url" id="image_url" accept="image/png, image/jpeg, image/webp"
                        class="tw-block tw-w-full tw-text-sm tw-text-gray-500 file:tw-mr-4 file:tw-py-1.5 file:tw-px-4 file:tw-rounded-sm file:tw-border-0 file:tw-text-sm file:tw-font-medium file:tw-bg-[#0078D4] file:tw-text-white hover:file:tw-bg-[#106ebe] file:tw-cursor-pointer tw-cursor-pointer tw-transition-colors"
                        onchange="previewBannerImage(this)">
                    <p class="tw-mt-1.5 tw-text-[11px] tw-text-gray-500">Hỗ trợ PNG, JPG, WEBP. Tối đa 2MB.</p>
                </div>
            </div>
        </div>

        <div>
            <label for="image_public_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">Image Public ID</label>
            <input type="text" name="image_public_id" id="image_public_id" value="{{ $banner->image_public_id }}"
                placeholder=""
                readonly
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-bg-gray-50 tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div>
            <label for="link" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">Liên kết</label>
            <input type="url" name="link" id="link" value="{{ $banner->link }}"
                placeholder="https://example.com"
                class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div>
                <label for="sort_order" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">Thứ tự
                    hiển thị</label>
                <input type="number" name="sort_order" id="sort_order" min="0" placeholder="0" value="{{ $banner->sort_order }}"
                    class="tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-[#0078D4] focus:tw-ring-[#0078D4] tw-text-sm tw-px-3 tw-py-2 tw-transition-colors tw-outline-none">
            </div>

            <div
                class="tw-flex tw-items-start tw-gap-3 tw-rounded-md tw-border tw-border-gray-200 tw-bg-gray-50 tw-px-3 tw-py-3">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    {{ $banner->is_active ? 'checked' : '' }}
                    class="tw-mt-1 tw-rounded tw-border-gray-300 tw-text-[#0078D4] focus:tw-ring-[#0078D4]">
                <div>
                    <label for="is_active" class="tw-text-sm tw-font-medium tw-text-gray-800">Trạng thái hoạt
                        động</label>
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">Cho phép banner này hiển thị ở trang chủ.</p>
                </div>
            </div>
        </div>
    </div>
</form>
