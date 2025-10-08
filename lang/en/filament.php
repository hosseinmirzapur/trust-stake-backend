<?php

return [
    'navigation' => [
        'system_management' => 'System Management',
    ],

    'resources' => [
        'whatsapp_numbers' => [
            'navigation_label' => 'WhatsApp Numbers',
            'plural_label' => 'WhatsApp Numbers',
            'singular_label' => 'WhatsApp Number',

            'sections' => [
                'basic_information' => 'Basic Information',
                'status_configuration' => 'Status & Configuration',
                'statistics' => 'Statistics',
                'session_management' => 'Session Management',
                'session_management_description' => 'WhatsApp session management actions',
            ],

            'fields' => [
                'mobile' => 'Mobile Number',
                'mobile_help' => 'Enter the virtual mobile number without country code',
                'session_id' => 'Session ID',
                'session_id_help' => 'Unique session identifier for this WhatsApp connection',
                'name' => 'Display Name',
                'name_help' => 'Friendly name for easy identification',
                'description' => 'Description',
                'status' => 'Status',
                'status_help' => 'Current status of the WhatsApp connection',
                'is_active' => 'Active',
                'is_active_help' => 'Whether this number is available for OTP sending',
                'connected_at' => 'Connected At',
                'last_used_at' => 'Last Used',
                'usage_count' => 'Usage Count',
                'usage_count_help' => 'Number of times this number has been used',
                'error_count' => 'Error Count',
                'error_count_help' => 'Number of errors encountered',
                'settings' => 'Additional Settings',
                'settings_key' => 'Setting',
                'settings_value' => 'Value',
                'settings_help' => 'Additional configuration settings as key-value pairs',
                'created_at' => 'Created At',
            ],

            'placeholders' => [
                'name' => 'Production Number 1',
                'description' => 'Optional description or notes about this number',
                'never' => 'Never',
            ],

            'options' => [
                'status' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'connected' => 'Connected',
                    'disconnected' => 'Disconnected',
                    'error' => 'Error',
                ],
            ],

            'actions' => [
                'test_connection' => 'Test Connection',
                'restart_session' => 'Restart Session',
                'start_session' => 'Start New Session',
                'show_qr_code' => 'Show QR Code',
                'request_pairing_code' => 'Request Pairing Code',
                'activate_selected' => 'Activate Selected',
                'deactivate_selected' => 'Deactivate Selected',
                'test_connections' => 'Test Connections',
            ],

            'table' => [
                'mobile' => 'Mobile',
                'name' => 'Name',
                'session_id' => 'Session ID',
                'status' => 'Status',
                'is_active' => 'Active',
                'connected_at' => 'Connected At',
                'last_used_at' => 'Last Used',
                'usage_count' => 'Usage',
                'error_count' => 'Errors',
                'created_at' => 'Created',
            ],

            'filters' => [
                'status' => 'Status',
                'is_active' => 'Active Status',
                'all_numbers' => 'All Numbers',
                'only_active' => 'Only Active',
                'only_inactive' => 'Only Inactive',
                'never_used' => 'Never Used',
                'has_errors' => 'Has Errors',
            ],

            'validation' => [
                'mobile_unique' => 'This mobile number is already registered.',
                'session_id_unique' => 'This session ID is already in use.',
            ],
        ],
    ],
];
