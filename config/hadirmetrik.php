<?php

return [
    'leave' => [
        // Organisation defaults from docs/architecture.md §6.4.
        'annual_quota_days' => (int) env('LEAVE_ANNUAL_QUOTA_DAYS', 12),
        'max_izin_per_month' => (int) env('LEAVE_MAX_IZIN_PER_MONTH', 2),
        'max_special_days' => (int) env('LEAVE_MAX_SPECIAL_DAYS', 3),
    ],
];
