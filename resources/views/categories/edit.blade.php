<form id="form-edit-category" action="{{ route('categories.update', $category->id) }}" method="POST"
    class="tw-flex tw-flex-col tw-h-full" novalidate>
    @csrf
    @method('PUT')

    <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100 tw-bg-white">
        <div>
            <h3 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-tracking-tight">{{ __('category.edit_title') }}</h3>
            <p class="tw-text-sm tw-text-gray-500 tw-mt-0.5">{{ __('category.edit_subtitle') }}</p>
        </div>

        <div>
            <button type="submit" id="submit-edit-category" class="fluent-btn-submit">
                {{ __('category.save_edit') }}
            </button>
        </div>
    </div>

    <div class="tw-px-6 tw-py-5 tw-bg-white tw-overflow-y-auto tw-flex-1 tw-space-y-5">
        <div class="tw-flex tw-flex-col tw-gap-1">
            <x-label for="name" class="is-required">{{ __('category.category_name') }}</x-label>
            <x-input id="name" name="name" required :value="$category->name"
                placeholder="{{ __('category.name_placeholder') }}" />
        </div>

        <div class="tw-flex tw-flex-col tw-gap-1">
            <x-label for="description" class="is-required">{{ __('category.description') }}</x-label>
            <x-textarea id="description" name="description" rows="3" required
                placeholder="{{ __('category.description_placeholder') }}">{{ $category->description }}</x-textarea>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <x-select id="parent_id" name="parent_id" title="{{ __('category.parent') }}">
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected($category->parent_id == $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </x-select>

            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="sort_order">{{ __('category.order') }}</x-label>
                <x-input type="number" min="0" id="sort_order" name="sort_order" :value="$category->sort_order" />
            </div>
        </div>

        <div class="tw-flex tw-items-center tw-gap-4">
            <x-switch name="is_active" value="0" :checked="$category->is_active" />
            <div>
                <label for="is_active" class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('category.active_label') }}</label>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5">{{ __('category.active_hint') }}</p>
            </div>
        </div>
    </div>
</form>
