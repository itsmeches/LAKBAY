<?php

return [
    'timezone' => 'Asia/Manila',
    'holiday_calendar_version' => env('LAKBAY_HOLIDAY_VERSION', 'unconfirmed-2026'),
    'allow_draft_policy' => env('LAKBAY_ALLOW_DRAFT_POLICY', false),
    'incident_hours' => 24,
    'incident_deadline_confirmed' => false,
    'change_hours' => 72,
    'scanner_command' => env('LAKBAY_SCANNER_COMMAND', ''),
    'scanner_database_path' => env('LAKBAY_SCANNER_DATABASE_PATH'),
    'upload_limit_kb' => 20480,
];
