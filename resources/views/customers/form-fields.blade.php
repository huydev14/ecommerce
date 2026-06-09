<div class="tw-flex-1 tw-min-h-0 tw-overflow-y-auto tw-p-6 tw-flex tw-flex-col tw-gap-5">
    <div class="tw-space-y-4">
        <div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-900">
                {{ $customer ? __('customer.edit_title') : __('customer.create_title') }}
            </h3>
            <p class="tw-mt-1 tw-text-xs tw-text-gray-500">
                {{ $customer ? __('customer.edit_subtitle') : __('customer.create_subtitle') }}
            </p>
        </div>

        <div class="tw-flex tw-flex-col tw-gap-1">
            <x-label for="name" class="is-required">{{ __('customer.name') }}</x-label>
            <x-input id="name" name="name" icon="far fa-user" :value="$customer?->name"
                placeholder="{{ __('customer.name') }}"/>
        </div>

        <div class="tw-flex tw-flex-col tw-gap-1">
            <x-label for="email" class="is-required">{{ __('customer.email') }}</x-label>
            <x-input type="email" id="email" name="email" icon="far fa-envelope" :value="$customer?->email"
                placeholder="email@example.com" required />
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="phone">{{ __('customer.phone') }}</x-label>
                <x-input type="tel" id="phone" name="phone" icon="fas fa-phone-alt" :value="$customer?->phone"
                    placeholder="09xx..." />
            </div>

            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="password" class="{{ !$customer ? 'is-required' : '' }}">{{ __('customer.password') }}</x-label>
                <x-input type="password" id="password" name="password" icon="fas fa-lock"
                    placeholder="{{ $customer ? __('customer.password_keep') : __('customer.password') }}"
                    :required="!$customer" />
            </div>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <x-select title="{{ __('customer.membership_tier') }}" id="membership_tier" name="membership_tier" required>
                @foreach ($tiers as $tierValue => $tierLabel)
                    <option value="{{ $tierValue }}" @selected(($customer?->membership_tier ?? 'standard') === $tierValue)>
                        {{ $tierLabel }}
                    </option>
                @endforeach
            </x-select>

            <div class="tw-flex tw-flex-col tw-gap-1">
                <x-label for="points">{{ __('customer.points') }}</x-label>
                <x-input type="number" min="0" id="points" name="points" icon="fas fa-star"
                    :value="$customer?->points ?? 0" />
            </div>
        </div>

        <label class="tw-flex tw-items-start tw-gap-3 tw-rounded tw-border tw-border-gray-200 tw-bg-gray-50 tw-p-3">
            <input type="checkbox" name="is_active" value="1"
                class="tw-mt-0.5 tw-rounded tw-border-gray-300 tw-text-[#0063B1] focus:tw-ring-[#0063B1]"
                @checked($customer?->is_active ?? true)>
            <span>
                <span class="tw-block tw-text-sm tw-font-medium tw-text-gray-900">{{ __('customer.active') }}</span>
                <span class="tw-block tw-text-xs tw-text-gray-500">{{ __('customer.status') }}</span>
            </span>
        </label>
    </div>
</div>
