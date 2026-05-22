@props(['title' => config('app.name'), 'description' => ''])

<div class="tw-flex tw-px-6 tw-py-3  sm:tw-flex-row tw-justify-between tw-gap-4">
    <div>
        <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">
            {{ $title }}
        </h2>
        <p class="tw-text-sm tw-text-gray-500 tw-mt-1">
            {{ $description }}
        </p>
    </div>

    {{ $slot }}
</div>
