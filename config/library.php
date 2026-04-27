<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Loan rules
    |--------------------------------------------------------------------------
    |
    | loan_days        - default number of days a copy may be kept
    | renew_days       - number of days added when a loan is renewed
    | max_active_loans - how many copies one member may hold at the same time
    |
    */

    'loan_days' => (int) env('LIBRARY_LOAN_DAYS', 14),

    'renew_days' => (int) env('LIBRARY_RENEW_DAYS', 7),

    'max_active_loans' => (int) env('LIBRARY_MAX_ACTIVE_LOANS', 5),

    /*
    |--------------------------------------------------------------------------
    | First librarian account
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Номын санч'),
        'email' => env('ADMIN_EMAIL', 'admin@lms.local'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

];
