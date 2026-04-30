@props(['title' => config('app.name'), 'description' => ''])

<div
    class="tw-flex tw-flex-col tw-px-6 tw-py-3  sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
    <div>
        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">
            {{ $title }}
        </h2>
        <p class="tw-text-sm tw-text-gray-500 tw-mt-1">
            {{ $description }}
        </p>
    </div>

    @if (isset($action))
        <div class="tw-flex tw-items-center tw-gap-3">
            {{ $action }}
            {{-- <button
                    class="tw-bg-white tw-border tw-border-gray-200 hover:tw-bg-gray-50 tw-text-gray-600 tw-p-2 tw-rounded-lg tw-transition-colors tw-shadow-sm">
                    <svg class="tw-w-5 tw-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                        </path>
                    </svg>
                </button> --}}
        </div>
    @endif
</div>
