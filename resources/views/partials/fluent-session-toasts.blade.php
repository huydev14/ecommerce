@if (session('success'))
    fluentToast({
        type: 'success',
        title: "{{ __('actions.success') }}",
        description: @js(session('success')),
        subtitle: 'Code: 200',
        actionType: 'close',
    });
@endif

@if (session('error'))
    fluentToast({
        type: 'error',
        title: "{{ __('actions.error') }}",
        description: @js(session('error')),
        actionType: 'close',
    });
@endif
