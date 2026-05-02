@props(['name', 'value' => '1', 'label', 'id' => null, 'checked' => false])

@php
    $inputId = $id ?? $name . '_' . Str::random(5);
@endphp

<label for="{{ $inputId }}"
    {{ $attributes->merge(['class' => 'tw-flex tw-items-center tw-cursor-pointer tw-group tw-w-fit']) }}>

    <input type="checkbox" id="{{ $inputId }}" name="{{ $name }}" value="{{ $value }}"
        {{ $checked ? 'checked' : '' }} class="tw-peer tw-sr-only">

    <div
        class="tw-w-4 tw-h-4 tw-border tw-border-gray-300 tw-rounded tw-bg-white group-hover:tw-border-gray-500 peer-checked:tw-bg-[#0078D4] peer-checked:tw-border-[#0078D4] tw-transition-colors tw-flex tw-items-center tw-justify-center peer-checked:[&>svg]:tw-opacity-100 peer-checked:[&>svg]:tw-scale-100 tw-shrink-0">

        <svg class="tw-w-3 tw-h-3 tw-text-white tw-opacity-0 tw-scale-50 tw-transition-all tw-duration-200 tw-ease-out"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>

    </div>

    <span class="tw-ml-3 tw-text-sm tw-text-gray-500 tw-font-medium peer-checked:tw-text-gray-800 tw-select-none">
        {{ $label }}
    </span>

</label>
