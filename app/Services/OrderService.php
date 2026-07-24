<?php

namespace App\Services;

class OrderService
{
    /**
     * Tạo order code với base32 encoding
     * @return string order code: YYMMDD + mã số 6 chữ cái
     */
    public static function generateOrderCode(int $id): string
    {
        $chars = '123456789ABCDEFGHJKLMNQRSTUVWXYZ';
        $seq = $id;
        $encoded = '';

        while ($seq > 0) {
            $encoded = $chars[$seq % 32] . $encoded;
            $seq = intdiv($seq, 32);
        }

        return date('ymd') . $encoded;
    }
}
