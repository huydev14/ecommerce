@props(['maxWidth' => 'tw-max-w-3xl'])

<div id="modal"
    class="tw-fixed tw-inset-0 tw-z-50 tw-hidden tw-items-center tw-justify-center tw-bg-gray-900/40 tw-backdrop-blur-sm tw-transition-opacity tw-p-4">
    <div
        class="tw-relative tw-w-full {{ $maxWidth }} tw-rounded-[4px] tw-bg-white tw-shadow-[0_6.4px_14.4px_0_rgba(0,0,0,0.132),0_1.2px_3.6px_0_rgba(0,0,0,0.108)] tw-border tw-border-gray-200 tw-flex tw-flex-col tw-max-h-[90vh] tw-overflow-hidden">

        {{ $slot }}

        <div class="tw-flex tw-justify-end tw-gap-2 tw-border-t tw-border-gray-100 tw-bg-gray-50 tw-px-4 tw-py-3">
            <button type="button" onclick="ModalHelper.close('modal')" class="fluent-btn-cancel">
                Đóng lại
            </button>
        </div>
    </div>
</div>
