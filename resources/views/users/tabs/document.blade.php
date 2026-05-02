<div id="tab-documents" class="tab-pane tw-hidden">
    {{-- Header & Nút Upload --}}
    <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
        <h3 class="tw-text-base tw-font-semibold tw-text-gray-900">{{ __('user.archived_records') }}</h3>
        <button type="button"
            class="tw-bg-[#0063B1] hover:tw-bg-blue-700 tw-text-white tw-px-4 tw-py-2 tw-rounded-sm tw-text-sm tw-font-medium tw-shadow-sm tw-transition-colors tw-flex tw-items-center tw-gap-2">
            <i class="fas fa-cloud-upload-alt"></i>
            {{ __('user.upload_document') }}
        </button>
    </div>

    <div class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-sm tw-shadow-sm tw-overflow-hidden tw-mb-8">
        <div
            class="tw-bg-gray-50 tw-px-5 tw-py-3 tw-border-b tw-border-gray-200 tw-flex tw-items-center tw-justify-between">
            <h4 class="tw-text-sm tw-font-semibold tw-text-gray-700">{{ __('user.contracts_and_commitments') }}</h4>
            <span class="tw-text-xs tw-text-gray-500 tw-font-medium">2 tập tin</span>
        </div>

        <ul class="tw-divide-y tw-divide-gray-100">
            <li
                class="tw-group tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3.5 hover:tw-bg-gray-50 tw-transition-colors">
                <div class="tw-flex tw-items-center tw-gap-4 tw-min-w-0">
                    <div
                        class="tw-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-bg-red-50 tw-rounded">
                        <i class="fas fa-file-pdf tw-text-red-500 tw-text-lg"></i>
                    </div>
                    <div class="tw-min-w-0">
                        <a href="#"
                            class="tw-text-sm tw-font-medium tw-text-gray-900 hover:tw-text-[#0063B1] tw-truncate tw-block">HopDongLaoDong_ChinhThuc_2026.pdf</a>
                        <div class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mt-0.5">
                            <span>{{ __('user.uploaded_at') }} 01/01/2026</span>
                            <span class="tw-w-1 tw-h-1 tw-bg-gray-300 tw-rounded-full"></span>
                            <span>2.4 MB</span>
                        </div>
                    </div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-1 tw-ml-4">
                    <button
                        class="tw-w-8 tw-h-8 tw-rounded tw-flex tw-items-center tw-justify-center tw-text-gray-400 hover:tw-bg-gray-200 hover:tw-text-gray-700 tw-transition-colors"
                        title="Xem trước">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button
                        class="tw-w-8 tw-h-8 tw-rounded tw-flex tw-items-center tw-justify-center tw-text-gray-400 hover:tw-bg-blue-50 hover:tw-text-[#0063B1] tw-transition-colors"
                        title="Tải xuống">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </li>

            <li
                class="tw-group tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3.5 hover:tw-bg-gray-50 tw-transition-colors">
                <div class="tw-flex tw-items-center tw-gap-4 tw-min-w-0">
                    <div
                        class="tw-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-bg-blue-50 tw-rounded">
                        <i class="fas fa-file-word tw-text-[#2B579A] tw-text-lg"></i>
                    </div>
                    <div class="tw-min-w-0">
                        <a href="#"
                            class="tw-text-sm tw-font-medium tw-text-gray-900 hover:tw-text-[#0063B1] tw-truncate tw-block">CamKetBaoMat_NDA_Signed.docx</a>
                        <div class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mt-0.5">
                            <span>{{ __('user.uploaded_at') }} 15/10/2025</span>
                            <span class="tw-w-1 tw-h-1 tw-bg-gray-300 tw-rounded-full"></span>
                            <span>1.1 MB</span>
                        </div>
                    </div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-1 tw-ml-4">
                    <button
                        class="tw-w-8 tw-h-8 tw-rounded tw-flex tw-items-center tw-justify-center tw-text-gray-400 hover:tw-bg-gray-200 hover:tw-text-gray-700 tw-transition-colors"
                        title="Xem trước">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button
                        class="tw-w-8 tw-h-8 tw-rounded tw-flex tw-items-center tw-justify-center tw-text-gray-400 hover:tw-bg-blue-50 hover:tw-text-[#0063B1] tw-transition-colors"
                        title="Tải xuống">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </li>
        </ul>
    </div>

    <div class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-sm tw-shadow-sm tw-overflow-hidden">
        <div
            class="tw-bg-gray-50 tw-px-5 tw-py-3 tw-border-b tw-border-gray-200 tw-flex tw-items-center tw-justify-between">
            <h4 class="tw-text-sm tw-font-semibold tw-text-gray-700">{{ __('user.id_and_degrees') }}</h4>
            <span class="tw-text-xs tw-text-gray-500 tw-font-medium">1 tập tin</span>
        </div>

        <ul class="tw-divide-y tw-divide-gray-100">
            <li
                class="tw-group tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3.5 hover:tw-bg-gray-50 tw-transition-colors">
                <div class="tw-flex tw-items-center tw-gap-4 tw-min-w-0">
                    <div
                        class="tw-shrink-0 tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-bg-gray-100 tw-rounded">
                        <i class="fas fa-file-image tw-text-gray-500 tw-text-lg"></i>
                    </div>
                    <div class="tw-min-w-0">
                        <a href="#"
                            class="tw-text-sm tw-font-medium tw-text-gray-900 hover:tw-text-[#0063B1] tw-truncate tw-block">Ban_Scan_CCCD_MatTruoc.jpg</a>
                        <div class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mt-0.5">
                            <span>{{ __('user.uploaded_at') }} 10/10/2025</span>
                            <span class="tw-w-1 tw-h-1 tw-bg-gray-300 tw-rounded-full"></span>
                            <span>850 KB</span>
                        </div>
                    </div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-1 tw-ml-4">
                    <button
                        class="tw-w-8 tw-h-8 tw-rounded tw-flex tw-items-center tw-justify-center tw-text-gray-400 hover:tw-bg-gray-200 hover:tw-text-gray-700 tw-transition-colors"
                        title="Xem trước">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button
                        class="tw-w-8 tw-h-8 tw-rounded tw-flex tw-items-center tw-justify-center tw-text-gray-400 hover:tw-bg-blue-50 hover:tw-text-[#0063B1] tw-transition-colors"
                        title="Tải xuống">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </li>
        </ul>
    </div>
</div>
