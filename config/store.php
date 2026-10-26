<?php

return [
    // رمز الدولة لتوحيد أرقام الهواتف المدخلة بلا رمز دولة (اليمن)
    'default_country_code' => env('STORE_DEFAULT_COUNTRY_CODE', '967'),

    // بادئة رقم الطلب
    'order_prefix' => env('STORE_ORDER_PREFIX', 'FD'),
];
