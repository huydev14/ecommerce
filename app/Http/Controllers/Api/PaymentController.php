<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
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

        // Get configs
        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url = config('vnpay.url');
        $vnp_Returnurl = config('vnpay.return_url');

        // Preapre parameters
        $vnp_TxnRef = $order->id . '_' . time();
        $vnp_OrderInfo = "Thanh toan don hang " . $order->id;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = (int) round($order->total_amount) * 100;
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $request->ip();

        $inputData = [
            'vnp_Version' => '2.1.0',
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        // Create Signature
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
        $vnp_Url = $vnp_Url . "?" . $query;

        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
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
        $inputData = $request->all();
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

        $vnp_HashSecret = config('vnpay.hash_secret');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Check signature validation
        if ($secureHash !== $vnp_SecureHash) {
            \Log::warning('VNPAY IPN: Sai chữ ký bảo mật', ['data' => $request->all()]);
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
        if ($order->total_amount != $vnp_Amount) {
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        if ($order->payment_status !== 'pending') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        // Update order status
        if ($inputData['vnp_ResponseCode'] == '00' && $inputData['vnp_TransactionStatus'] == '00') {
            $order->payment_status = 'paid';
            $order->status = 'processing';
            \Log::info("VNPAY IPN: Thanh toán thành công đơn hàng {$orderId}");
        } else {
            $order->payment_status = 'failed';
            \Log::info("VNPAY IPN: Thanh toán thất bại đơn hàng {$orderId}");
        }
        $order->save();

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }
}
