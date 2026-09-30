<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Personal data processing policy (Colombia, Ley 1581 de 2012)
    |--------------------------------------------------------------------------
    |
    | The version is stored with every acceptance. Raise it whenever the text of
    | the policy changes in a way users should accept again.
    |
    | The controller data (razón social, NIT, address, phone) is shown on the
    | policy page only when it is set, so no made-up data is ever published.
    |
    */

    'version' => '1.0',

    'updated_at' => '2026-09-30',

    'company' => env('PRIVACY_COMPANY', 'Médicos Integrados'),

    'nit' => env('PRIVACY_NIT'),

    'address' => env('PRIVACY_ADDRESS'),

    'phone' => env('PRIVACY_PHONE'),

    'contact_email' => env('PRIVACY_CONTACT_EMAIL', 'contacto@medicosintegrados.test'),

];
