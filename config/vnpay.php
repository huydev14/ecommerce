<?php

return [
    'tmn_code' => env('VNP_TMNCODE'),
    'hash_secret' => env('VNP_HASHSECRET'),
    'url' => env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'return_url' => env('VNP_RETURN_URL'),
];