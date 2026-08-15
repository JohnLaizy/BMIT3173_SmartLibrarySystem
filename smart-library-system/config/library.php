<?php

return [
    'borrow_limit' => (int) env(
        'LIBRARY_BORROW_LIMIT',
        5
    ),

    'loan_days' => (int) env(
        'LIBRARY_LOAN_DAYS',
        7
    ),

    'overdue_fee_cents_per_day' => (int) env(
        'LIBRARY_OVERDUE_FEE_CENTS_PER_DAY',
        100
    ),

    'reservation_hold_days' => (int) env(
        'LIBRARY_RESERVATION_HOLD_DAYS',
        2
    ),

    /*
     * Public website destinations used only by the simulated-payment flow.
     * No credentials, accounts, bank callback or real transaction data are
     * sent to or received from these links.
     */
    'simulated_payment_gateways' => [
        'maybank' => [
            'url' => 'https://www.maybank2u.com.my/',
        ],

        'cimb' => [
            'url' => 'https://www.cimb.com.my/',
        ],

        'public_bank' => [
            'url' => 'https://www.pbebank.com/',
        ],
    ],
];
