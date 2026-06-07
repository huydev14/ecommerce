<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * API: Create URL VNPAY PAYMENT
     */
    public function createPayment(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thanh toán đơn hàng này.'], 403);
        }

        if ($order->payment_method !== 'vnpay') {
            return response()->json(['success' => false, 'message' => 'Đơn hàng không sử dụng phương thức VNPAY.'], 400);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Đơn hàng đã được thanh toán.'], 400);
        }

        // Get config from cache
        $vnpConfig = $this->getVnpayConfig();

        if (
            blank($vnpConfig['tmn_code'] ?? null)
            || blank($vnpConfig['hash_secret'] ?? null)
            || blank($vnpConfig['url'] ?? null)
            || blank($vnpConfig['return_url'] ?? null)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Cấu hình VNPAY chưa đầy đủ hoặc chưa được kích hoạt.',
            ], 422);
        }

        $vnp_TxnRef = $order->id . '_' . time();
        $vnp_OrderInfo = "Thanh toan don hang " . $order->id;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = (int) round($order->total_amount) * 100;
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $request->ip();

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnpConfig['tmn_code'],
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnpConfig['return_url'],
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        // Create signature
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnpConfig['url'] . "?" . $query;

        if (!empty($vnpConfig['hash_secret'])) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnpConfig['hash_secret']);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo URL thanh toán VNPAY thành công.',
            'data' => [
                'payment_url' => $vnp_Url,
            ],
        ]);
    }

    /**
     * API: Webhook (IPN) VNPAY
     */
    public function vnpayIpn(Request $request)
    {
        Log::warning('VNPAY đã gọi IPN !!!');
        $inputData = [];
        $allRequestData = $request->all();

        foreach ($allRequestData as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $vnpConfig = $this->getVnpayConfig();

        if (
            blank($vnpConfig['tmn_code'] ?? null)
            || blank($vnpConfig['hash_secret'] ?? null)
            || blank($vnpConfig['url'] ?? null)
            || blank($vnpConfig['return_url'] ?? null)
        ) {
            Log::error('VNPAY IPN: Cấu hình VNPAY chưa đầy đủ hoặc chưa được kích hoạt.');
            return response()->json(['RspCode' => '99', 'Message' => 'VNPAY config missing']);
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnpConfig['hash_secret']);

        // Check signature validation
        if ($secureHash !== $vnp_SecureHash) {
            Log::warning('VNPAY IPN: Sai chữ ký bảo mật', ['data' => $request->all()]);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        // Find order
        $txnRefParts = explode('_', $inputData['vnp_TxnRef']);
        $orderId = $txnRefParts[0];
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $vnp_Amount = $inputData['vnp_Amount'] / 100;
        if (floatval($order->total_amount) !== floatval($vnp_Amount)) {
            Log::error("VNPAY IPN: Sai số tiền. Đơn hàng: {$order->total_amount}, VNPAY gửi: {$vnp_Amount}");
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        if ($order->payment_status !== 'pending') {
            Log::warning("VNPAY IPN: Đơn hàng {$orderId} đã được xác nhận trước đó");
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        // Update order status
        if ($inputData['vnp_ResponseCode'] == '00' && $inputData['vnp_TransactionStatus'] == '00') {
            $order->payment_status = 'paid';
            $order->status = 'processing';
            Log::info("VNPAY IPN: Thanh toán thành công đơn hàng {$orderId}");
        } else {
            $order->payment_status = 'failed';
            Log::info("VNPAY IPN: Thanh toán thất bại đơn hàng {$orderId}");
        }
        $order->save();

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    /**
     * Helper: Get VNPAY config dynamically
     */
    private function getVnpayConfig(): array
    {
        $settings = Cache::rememberForever('config.integrations.vnpay', function () {
            return Setting::where('group', 'integrations')
                ->where('key', 'vnpay')
                ->first()?->value;
        });

        if (empty($settings['is_active'])) {
            return [
                'tmn_code' => config('vnpay.tmn_code'),
                'hash_secret' => config('vnpay.hash_secret'),
                'url' => config('vnpay.url'),
                'return_url' => config('vnpay.return_url'),
            ];
        }

        return [
            'tmn_code' => ($settings['tmn_code'] ?? '') ?: config('vnpay.tmn_code'),
            'hash_secret' => !empty($settings['hash_secret'])
                ? decrypt($settings['hash_secret'])
                : config('vnpay.hash_secret'),
            'url' => ($settings['url'] ?? '') ?: config('vnpay.url'),
            'return_url' => ($settings['return_url'] ?? '') ?: config('vnpay.return_url'),
        ];
    }
}
