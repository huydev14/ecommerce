@extends('layouts.main')

@section('page-header')
    <x-page-header title="Home" description="Dashboard & Analysis"/>
@endsection

@section('content')
    <div class="row">
      
       
    </div>
    <!-- /.row -->
@endsection

@push('scripts')
    <script>
        $(function() {
            @include('partials.fluent-session-toasts')
        });
    </script>
@endpush
