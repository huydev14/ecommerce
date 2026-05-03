<!DOCTYPE html>
<html>
<head>
    <title>Authenticating...</title>
</head>
<body>
    <script>
        const payload = {
            token: "{{ $token ?? '' }}",
            user: {!! isset($user) ? json_encode($user) : 'null' !!},
            error: "{{ $error ?? '' }}"
        };
        if (window.opener) {
            window.opener.postMessage(payload, "*");
            window.close();
        } else {
            window.location.href = "/";
        }
    </script>
</body>
</html>
