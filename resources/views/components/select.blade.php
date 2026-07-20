@props([
    'id' => '',
    'name' => '',
    'title' => '',
    'required' => '',
    'options' => [],
    'placeholder' => true,
])

<div class="tw-flex tw-flex-col tw-gap-1">
    <label class="fluent-label @if ($required) is-required @endif"
        for="{{ $id }}">{{ ucfirst($title) }}
    </label>

    <div
        class="tw-relative tw-flex tw-items-center tw-bg-white tw-border tw-border-gray-300 tw-border-b-gray-400 tw-rounded-[4px] tw-overflow-hidden tw-transition-colors hover:tw-border-gray-400 focus-within:tw-border-gray-300 has-[:disabled]:tw-border-[#E0E0E0] has-[:disabled]:tw-bg-transparent after:tw-content-[''] after:tw-absolute after:tw-bottom-0 after:tw-left-0 after:tw-right-0 after:tw-h-[2px] after:tw-bg-[#0063B1] after:tw-scale-x-0 focus-within:after:tw-scale-x-100 after:tw-transition-transform after:tw-duration-200 after:tw-origin-center">
        <select name="{{ $name }}" id="{{ $id }}" @if($required) required @endif
            {{ $attributes->merge(['class' => 'tw-w-full tw-py-1.5 tw-px-2.5 tw-text-sm tw-text-gray-900 tw-bg-transparent tw-border-none tw-outline-none focus:tw-ring-0 tw-appearance-none disabled:tw-text-[#BDBDBD] disabled:tw-cursor-not-allowed']) }}>
            @if ($placeholder)
                <option value="">Chọn {{ strtolower($title) }}</option>
            @endif

            {{ $slot }}

            @if ($options)
                @foreach ($options as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            @endif

        </select>
        <i class="fas fa-chevron-down tw-absolute tw-right-3 tw-text-gray-500 tw-text-xs tw-pointer-events-none"></i>
    </div>
</div>
