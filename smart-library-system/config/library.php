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
];