{{-- Component: Textarea field --}}
@props([
    'id' => '',
    'placeholder' => '',
    'required' => '',
    'rows' => 3,
])

<div
    class="tw-relative tw-flex tw-bg-white tw-border tw-border-gray-300 tw-border-b-gray-400 tw-rounded-[4px] tw-overflow-hidden tw-transition-colors hover:tw-border-gray-400 focus-within:tw-border-gray-300 after:tw-content-[''] after:tw-absolute after:tw-bottom-0 after:tw-left-0 after:tw-right-0 after:tw-h-[2px] after:tw-bg-[#0063B1] after:tw-scale-x-0 focus-within:after:tw-scale-x-100 after:tw-transition-transform after:tw-duration-200 after:tw-origin-center">

    <textarea id="{{ $id }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'tw-w-full tw-py-1.5 tw-px-2.5 tw-text-sm tw-text-gray-900 tw-bg-transparent tw-border-none tw-outline-none focus:tw-ring-0 tw-resize-none placeholder:tw-text-gray-400']) }}>{{ $slot }}</textarea>
</div>
