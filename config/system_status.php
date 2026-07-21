<?php

return [
    'cache_seconds' => (int) env('SYSTEM_STATUS_CACHE_SECONDS', 300),
    'database_limit_bytes' => (int) env('DB_SIZE_LIMIT', 1024 * 1024 * 1024),
    'b2_limit_bytes' => (int) env('B2_SIZE_LIMIT', 10 * 1024 * 1024 * 1024),
];
