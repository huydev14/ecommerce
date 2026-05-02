@extends('layouts.main')


@section('content')
    <div class="container-fluid">
        <div class="show-content tw-py-4 tw-px-12">
            {{-- Header --}}
            <div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
                <div class="tw-flex tw-items-center tw-gap-3">
                    <a href="{{ route('users.index') }}"
                        class="tw-w-8 tw-h-8 tw-flex tw-items-center tw-justify-center tw-rounded-full hover:tw-bg-gray-200 tw-text-gray-600 tw-transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="tw-text-2xl tw-font-semibold tw-text-gray-900">{{ __('user.staff_profile') }}</h1>
                </div>

                <div class="tw-flex tw-gap-3">
                    <button type="button" class="fluent-btn-cancel edit-user-btn"
                        data-edit-url="{{ route('users.edit', $user->id) }}">
                        <i class="fas fa-pen tw-mr-2"></i> {{ __('actions.edit') }}
                    </button>
                </div>
            </div>

            <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-4">
                {{-- Left panel --}}
                <div class="lg:tw-col-span-4 tw-h-full">
                    <div class="fluent-card tw-sticky tw-top-6">
                        <div class="card-body tw-flex tw-flex-col tw-items-center tw-text-center tw-p-8">
                            {{-- Avatar  --}}
                            @php
                                $nameParts = explode(' ', trim($user->name));
                                $initials =
                                    count($nameParts) > 1
                                        ? strtoupper(substr(end($nameParts), 0, 1))
                                        : strtoupper(substr($user->name, 0, 1));
                                $colors = [
                                    'tw-bg-[#0078D4]',
                                    'tw-bg-[#D13438]',
                                    'tw-bg-[#038387]',
                                    'tw-bg-[#8764B8]',
                                    'tw-bg-[#498205]',
                                ];
                                $bgColor = $colors[$user->id % count($colors)];
                            @endphp
                            <div
                                class="tw-w-28 tw-h-28 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-text-4xl tw-font-semibold tw-shadow-md tw-mb-5 {{ $bgColor }}">
                                {{ $initials }}
                            </div>

                            <h2 class="tw-text-xl tw-font-bold tw-text-gray-900">{{ $user->name }}</h2>
                            <p class="tw-text-sm tw-text-gray-500 tw-mt-1">{{ $user->role->name ?? __('user.staff') }}</p>

                            <div class="tw-mt-4">
                                @if ($user->status === 0)
                                    <span
                                        class="tw-inline-flex tw-items-center tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium tw-bg-green-100 tw-text-green-800">
                                        {{ __('user.active') }}
                                    </span>
                                @else
                                    <span
                                        class="tw-inline-flex tw-items-center tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium tw-bg-gray-100 tw-text-gray-800">
                                        {{ __('user.inactive') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="tw-border-t tw-border-gray-100 tw-p-6">
                            <h3 class="tw-text-xs tw-font-semibold tw-text-gray-400 tw-uppercase tw-tracking-wider tw-mb-4">
                                {{ __('user.contact') }}
                            </h3>

                            <div class="tw-space-y-4">
                                <div class="tw-flex tw-items-start tw-gap-3">
                                    <i class="far fa-envelope tw-text-gray-400 tw-mt-1 tw-w-5"></i>
                                    <div>
                                        <div class="tw-text-sm tw-font-medium tw-text-gray-900 break-all">
                                            {{ $user->email }}
                                        </div>
                                        <div class="tw-text-xs tw-text-gray-500">{{ __('user.work_email') }}</div>
                                    </div>
                                </div>
                                <div class="tw-flex tw-items-start tw-gap-3">
                                    <i class="fas fa-phone-alt tw-text-gray-400 tw-mt-1 tw-w-5"></i>
                                    <div>
                                        <div class="tw-text-sm tw-font-medium tw-text-gray-900">
                                            {{ $user->phone ?? __('user.not_updated') }}</div>
                                        <div class="tw-text-xs tw-text-gray-500">{{ __('user.phone_number') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tw-border-t tw-border-gray-100 tw-p-6">
                            <h3 class="tw-text-xs tw-font-semibold tw-text-gray-400 tw-uppercase tw-tracking-wider tw-mb-4">
                                {{ __('user.direct_manager') }}
                            </h3>

                            <div
                                class="tw-flex tw-items-center tw-gap-3 tw-cursor-pointer hover:tw-bg-gray-50 tw-p-2 tw--ml-2 tw-rounded-lg tw-transition-colors">
                                <div
                                    class="tw-w-10 tw-h-10 tw-rounded-full tw-bg-blue-100 tw-text-blue-700 tw-flex tw-items-center tw-justify-center tw-font-bold tw-text-sm">
                                    H
                                </div>
                                <div>
                                    <div class="tw-text-sm tw-font-medium tw-text-gray-900">Hoàng Văn A</div>
                                    <div class="tw-text-xs tw-text-gray-500">Trưởng phòng IT</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right panel --}}
                <div class="lg:tw-col-span-8">
                    <div class="fluent-card tw-h-full">
                        {{-- Tab Navigation --}}
                        <div class="tw-border-b tw-border-gray-200 tw-px-6">
                            <nav class="tw--mb-px tw-flex tw-space-x-8" aria-label="Tabs">
                                @php
                                    $tabs = [
                                        'tab-overview' => __('user.tab_overview'),
                                        'tab-work' => __('user.tab_work'),
                                        'tab-timeline' => __('user.tab_timeline'),
                                        'tab-activity' => __('user.tab_activity'),
                                        'tab-documents' => __('user.tab_documents'),
                                    ];
                                @endphp
                                @foreach ($tabs as $id => $title)
                                    <button
                                        class="fluent-tab tw-whitespace-nowrap tw-py-4 tw-px-1 tw-border-b-2 tw-font-medium tw-text-sm tw-transition-colors hover:tw-border-gray-300 hover:tw-text-gray-700
                                        {{ $loop->first ? 'tw-border-[#0063B1] tw-text-[#0063B1]' : 'tw-border-transparent tw-text-gray-500 ' }}"
                                        data-target="#{{ $id }}">{{ $title }}
                                    </button>
                                @endforeach
                            </nav>
                        </div>

                        {{-- Tab Contents --}}
                        <div class="tw-p-6">
                            @include('users.tabs.overview')
                            @include('users.tabs.work')
                            @include('users.tabs.timeline')
                            @include('users.tabs.activities')
                            @include('users.tabs.document')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Panel: Edit user --}}
    <x-slide-over id="slideover-edit-user" title="{{ __('user.update_staff_info') }}">
        <div id="content-edit" class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0"></div>
    </x-slide-over>
@endsection

@push('scripts')
    <script type="module">
        $(function() {
            $('.fluent-tab').on('click', function() {
                $('.fluent-tab')
                    .removeClass('tw-border-[#0063B1] tw-text-[#0063B1]')
                    .addClass('tw-border-transparent tw-text-gray-500');
                $(this)
                    .addClass('tw-border-[#0063B1] tw-text-[#0063B1]')
                    .removeClass('tw-border-transparent tw-text-gray-500');

                $('.tab-pane').addClass('tw-hidden');
                $($(this).data('target')).removeClass('tw-hidden');
            });
        });

        $(document).on('click', '.edit-user-btn, #edit-user-btn', function() {
            let editUrl = $(this).data('edit-url');
            openSlideover('slideover-edit-user');
            $('#content-edit').html(loadingHtml);

            $.get(editUrl, function(html) {
                $('#content-edit').html(html);
            }).fail(function(xhr) {
                $('#content-edit').html(loadingHtml);
                console.error('Load edit form error:', xhr.status);
                console.error('Load edit form error:', xhr.responseText);
            });
        });

        ajaxUserFormRequest('#form-edit-user', null, true)
    </script>
@endpush
