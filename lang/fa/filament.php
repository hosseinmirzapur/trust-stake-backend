<?php

return [
    'navigation' => [
        'system_management' => 'مدیریت سیستم',
    ],

    'resources' => [
        'whatsapp_numbers' => [
            'navigation_label' => 'شماره‌های واتساپ',
            'plural_label' => 'شماره‌های واتساپ',
            'singular_label' => 'شماره واتساپ',

            'sections' => [
                'basic_information' => 'اطلاعات پایه',
                'status_configuration' => 'وضعیت و تنظیمات',
                'statistics' => 'آمار',
                'session_management' => 'مدیریت جلسه',
                'session_management_description' => 'اقدامات مدیریت جلسه واتساپ',
            ],

            'fields' => [
                'mobile' => 'شماره موبایل',
                'mobile_help' => 'شماره موبایل مجازی را بدون کد کشور وارد کنید',
                'session_id' => 'شناسه جلسه',
                'session_id_help' => 'شناسه منحصر به فرد برای اتصال واتساپ',
                'name' => 'نام نمایشی',
                'name_help' => 'نام دوستانه برای شناسایی آسان',
                'description' => 'توضیحات',
                'status' => 'وضعیت',
                'status_help' => 'وضعیت فعلی اتصال واتساپ',
                'is_active' => 'فعال',
                'is_active_help' => 'آیا این شماره برای ارسال OTP در دسترس است',
                'connected_at' => 'زمان اتصال',
                'last_used_at' => 'آخرین استفاده',
                'usage_count' => 'تعداد استفاده',
                'usage_count_help' => 'تعداد دفعاتی که از این شماره استفاده شده',
                'error_count' => 'تعداد خطا',
                'error_count_help' => 'تعداد خطاهای رخ داده',
                'settings' => 'تنظیمات اضافی',
                'settings_key' => 'تنظیم',
                'settings_value' => 'مقدار',
                'settings_help' => 'تنظیمات پیکربندی اضافی به صورت کلید-مقدار',
                'created_at' => 'زمان ایجاد',
            ],

            'placeholders' => [
                'name' => 'شماره تولید ۱',
                'description' => 'توضیحات اختیاری یا یادداشت در مورد این شماره',
                'never' => 'هرگز',
            ],

            'options' => [
                'status' => [
                    'active' => 'فعال',
                    'inactive' => 'غیرفعال',
                    'connected' => 'متصل',
                    'disconnected' => 'قطع شده',
                    'error' => 'خطا',
                ],
            ],

            'actions' => [
                'test_connection' => 'تست اتصال',
                'restart_session' => 'راه‌اندازی مجدد جلسه',
                'start_session' => 'شروع جلسه جدید',
                'show_qr_code' => 'نمایش کد QR',
                'request_pairing_code' => 'درخواست کد جفت‌سازی',
                'activate_selected' => 'فعال کردن انتخاب شده',
                'deactivate_selected' => 'غیرفعال کردن انتخاب شده',
                'test_connections' => 'تست اتصالات',
            ],

            'table' => [
                'mobile' => 'موبایل',
                'name' => 'نام',
                'session_id' => 'شناسه جلسه',
                'status' => 'وضعیت',
                'is_active' => 'فعال',
                'connected_at' => 'زمان اتصال',
                'last_used_at' => 'آخرین استفاده',
                'usage_count' => 'استفاده',
                'error_count' => 'خطاها',
                'created_at' => 'ایجاد شده',
            ],

            'filters' => [
                'status' => 'وضعیت',
                'is_active' => 'وضعیت فعال',
                'all_numbers' => 'همه شماره‌ها',
                'only_active' => 'فقط فعال',
                'only_inactive' => 'فقط غیرفعال',
                'never_used' => 'هرگز استفاده نشده',
                'has_errors' => 'دارای خطا',
            ],

            'validation' => [
                'mobile_unique' => 'این شماره موبایل قبلاً ثبت شده است.',
                'session_id_unique' => 'این شناسه جلسه در حال استفاده است.',
            ],
        ],
    ],
];
