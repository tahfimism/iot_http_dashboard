<?php
/**
 * Firefly IoT Configuration
 * Change these values to adjust platform-wide limits and behavior.
 */

return [
    // Telemetry Limits (Max records to keep per device)
    'telemetry' => [
        'max_records_free'    => 500,
        'max_records_premium' => 10000,
    ],

    // Device Limits (Max devices per user)
    'devices' => [
        'max_free'    => 3,
        'max_premium' => 25,
    ],
    
    // Auto-cleanup setting (optional future use)
    'retention' => [
        'enabled' => true,
    ]
];
?>
