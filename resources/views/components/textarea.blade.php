{{-- Component: Textarea field --}}
@props([
    'id' => '',
    'placeholder' => '',
    'required' => '',
    'rows' => 3,
    'resize' => 'none',
    'size' => 'medium',
])

@php
    $resizeClass = match ($resize) {
        'both' => 'tw-resize',
        'horizontal' => 'tw-resize-x',
        'vertical' => 'tw-resize-y',
        default => 'tw-resize-none',
    };

    $sizeClass = match ($size) {
        'small' => 'tw-text-xs tw-py-1 tw-px-2',
        'large' => 'tw-text-base tw-py-2 tw-px-3',
        default => 'tw-text-sm tw-py-1.5 tw-px-3',
    };
@endphp

<div
    class="tw-relative tw-flex tw-bg-white tw-rounded-[4px] tw-border tw-border-[#D1D1D1] tw-border-b-[#616161] tw-transition-colors has-[:disabled]:tw-border-[#E0E0E0] has-[:disabled]:tw-bg-transparent after:tw-content-[''] after:tw-absolute after:tw-left-0 after:tw-right-0 after:tw-bottom-0 after:tw-h-[2px] after:tw-bg-[#0F6CBD] after:tw-scale-x-0 focus-within:after:tw-scale-x-100 after:tw-transition-transform after:tw-duration-200 after:tw-origin-center">

    <textarea id="{{ $id }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => "tw-w-full tw-text-[#242424] tw-bg-transparent tw-border-none tw-outline-none focus:tw-ring-0 placeholder:tw-text-gray-400 disabled:tw-text-[#BDBDBD] disabled:tw-cursor-not-allowed {$sizeClass} {$resizeClass}"]) }}>{{ $slot }}</textarea>
</div>
