<?php

// Include the automatic detection function
require_once __DIR__ . '/Helpers/Helpers.php';

return [
    // The user identifier for the API, typically a public key.
    'apiUser' => 'pk_sandbox_261012052931cqRn9OC',

    // The secret key for authenticating API requests, keep this secure.
    'apiKey' => 'sk_sandbox_AFP121026ZGBtRpjREmKs1ansIQIw',

    // The merchant key, specific to the account of the merchant using the API.
    'merchantKey' => 'mk_sandbox_2610120529319E',

    // The unique identifier for the agent, used to track transactions or activities.
    'agent_id' => 'APM31923613',

    // Specifies the environment where the API is running: 'production' for live usage, or 'sandbox' for testing.
    'environment' => 'sandbox',

    // Sets the language for API responses, here 'fr' for French.
    'lang' => 'en',

    // URL that the payment system will notify (via POST) about the result/status of the transaction.
    // This endpoint should handle IPN (Instant Payment Notification) securely.
    // 'notify_url' => "https://www.examplee.com/checkout/notify", // Replace this with your own URL
    
    // Automatically detect the relative path to the checkout folder
    // This function calculates the correct path based on the environment 
    'checkoutPath' => modal_afp_autoDetectCheckoutPath(),
    // 'checkoutPath' => '/ck/modal',

    // Indicates whether to use a cache folder to store session/transaction data (recommended for server environments).
    // If false, fallback to client-side cookies.
    'useCacheFolder' => true,

    // Directory path to store cached data if 'useCacheFolder' is true.
    // `sys_get_temp_dir()` returns the system's temporary directory dynamically.
    'cacheDirectory' => sys_get_temp_dir(),

    // Bootstrap CSS used by the modal:
    //  - 'auto'  : load from public CDNs with automatic browser-side fallback (default)
    //  - string  : your own URL (self-hosted file or another CDN)
    //  - false   : do not inject anything (your site already loads Bootstrap 5)
    'bootstrapCss' => 'auto',

];
