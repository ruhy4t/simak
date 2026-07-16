<?php

return [
    'slow_request_ms' => (int) env('SLOW_REQUEST_MS', 1000),
    'slow_query_ms' => (int) env('SLOW_QUERY_MS', 250),
    'request_sample_rate' => (float) env('REQUEST_METRICS_SAMPLE_RATE', 0.1),
];
