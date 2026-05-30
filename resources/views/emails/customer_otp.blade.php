<!DOCTYPE html>
<html>
<head>
    <style>
        .container { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .otp-code { font-size: 30px; font-weight: bold; color: #000; margin: 20px 0; }
        .footer { font-size: 12px; color: #777; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">{{ env('APP_NAME') }}</div>
        <p>Xin chào {{ $customerName }},</p>
        <p>Để hoàn tất việc tạo tài khoản, vui lòng sử dụng mã xác minh (OTP) sau đây:</p>

        <div class="otp-code">{{ $otp }}</div>

        <p>Mã này có hiệu lực trong 10 phút. Vui lòng không chia sẻ mã này với bất kỳ ai.</p>

        <div class="footer">
            © {{ date('Y') }} {{ env('APP_NAME') }}.com.
        </div>
    </div>
</body>
</html>
