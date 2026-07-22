<?php

/**
 * Fixed package catalog keys and statuses.
 *
 * Quotas / features stored on packages + tenant_feature_overrides must use these keys only.
 * Admin UI must never allow free-typed keys.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Package statuses (catalog behavior)
    |--------------------------------------------------------------------------
    |
    | trial         — free/trial SKU attached at signup
    | active        — offered for new purchases
    | legacy        — existing subs may keep; not offered to new buyers
    | upgrade_only  — only as an upgrade target (not landing SKU)
    | hidden        — admin-only / internal
    |
    */

    'statuses' => [
        'trial' => 'Trial',
        'active' => 'Active',
        'legacy' => 'Legacy',
        'upgrade_only' => 'Upgrade only',
        'hidden' => 'Hidden',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quota keys (numeric limits)
    |--------------------------------------------------------------------------
    */

    'quotas' => [
        'max_qodes' => [
            'label' => 'Max Qodes',
            'type' => 'integer',
            'default' => 10,
        ],
        'max_scans' => [
            'label' => 'Max scans',
            'type' => 'integer',
            'default' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature flags (booleans)
    |--------------------------------------------------------------------------
    */

    'features' => [
        'custom_domains' => [
            'label' => 'Custom domains',
            'default' => false,
        ],
        'custom_slugs' => [
            'label' => 'Custom / vanity slugs',
            'default' => false,
        ],
        'platform_domain_choice' => [
            'label' => 'Choose platform short domain',
            'default' => false,
        ],
    ],

];
