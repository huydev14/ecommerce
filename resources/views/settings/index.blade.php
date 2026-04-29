@extends('layouts.main')

@section('content')
@section('page-header')
    <x-page-header title="Settings" description="Cài đặt chung cho toàn bộ hệ thống." />
@endsection

<div class=" tw-bg-[#f3f3f3] tw-min-h-screen">
    {{-- Alert Messages --}}
    @if (session('success'))
        <div class="tw-flex tw-items-center tw-p-4 tw-mb-6 tw-text-[#107c10] tw-bg-[#dff6dd] tw-rounded-sm tw-shadow-sm">
            <i class="fas fa-check-circle tw-mr-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-6">
        {{-- Navigation Left --}}
        <div class="lg:tw-col-span-3">
            <div class="nav tw-flex tw-flex-col fluent-tab-list tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-2"
                id="v-pills-tab" role="tablist">

                {{-- Group: Mail --}}
                <div class="tw-px-3 tw-py-2 tw-text-[10px] tw-font-bold tw-text-gray-400 tw-uppercase">Giao tiếp</div>
                <a class="nav-link fluent-tab-item tw-mb-1 tw-flex tw-items-center tw-gap-3" id="v-pills-mail-tab"
                    data-bs-toggle="pill" href="#v-pills-mail" data-tab="mail" role="tab">
                    <i class="fas fa-envelope tw-text-lg tw-w-5"></i>
                    <span class="tw-font-medium tw-text-sm">Cấu hình Mail</span>
                </a>

                <hr class="tw-my-2 tw-border-gray-100">

                {{-- Group: OAuth --}}
                <div class="tw-px-3 tw-py-2 tw-text-[10px] tw-font-bold tw-text-gray-400 tw-uppercase">Định danh</div>
                <a class="nav-link fluent-tab-item tw-mb-1 tw-flex tw-items-center tw-gap-3" id="v-pills-google-tab"
                    data-bs-toggle="pill" href="#v-pills-google" data-tab="google" role="tab">
                    <i class="fab fa-google tw-text-lg tw-w-5"></i>
                    <span class="tw-font-medium tw-text-sm">Google OAuth</span>
                </a>
                {{-- <a class="nav-link fluent-tab-item tw-mb-1 tw-flex tw-items-center tw-gap-3" id="v-pills-microsoft-tab"
                    data-bs-toggle="pill" href="#v-pills-microsoft" data-tab="microsoft" role="tab">
                    <i class="fab fa-microsoft tw-text-lg tw-w-5"></i>
                    <span class="tw-font-medium tw-text-sm">Microsoft OAuth</span>
                </a> --}}
            </div>
        </div>

        {{-- Content Right --}}
        <div class="lg:tw-col-span-9">
            <div class="tab-content" id="v-pills-tabContent">

                <div class="tab-pane fade" id="v-pills-mail" role="tabpanel">
                    @include('settings.partials._mail')
                </div>

                <div class="tab-pane fade" id="v-pills-google" role="tabpanel">
                    @include('settings.partials._google_oauth')
                </div>

                {{-- <div class="tab-pane fade" id="v-pills-microsoft" role="tabpanel">
                    @include('settings.partials._microsoft_oauth')
                </div> --}}
            </div>
        </div>
    </div>
</div>

<style>
    .fluent-tab-item {
        border-radius: 4px !important;
        color: #323130 !important;
    }

    .fluent-tab-item:hover {
        background-color: #f3f2f1 !important;
    }

    .fluent-tab-item.active {
        background-color: #edebe9 !important;
        color: #0078d4 !important;
        position: relative;
        font-weight: 600;
    }

    .fluent-tab-item.active::before {
        content: "";
        position: absolute;
        left: 0;
        top: 20%;
        height: 60%;
        width: 3px;
        background-color: #0078d4;
        border-radius: 2px;
    }
</style>
@endsection
