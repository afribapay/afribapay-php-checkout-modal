# AfribaPay PHP WebCheckout Modal Documentation

## Introduction

The **AfribaPay PHP WebCheckout modal** enables developers to integrate AfribaPay's payment gateway seamlessly into their applications. It simplifies payment initialization, checkout button generation, and response handling while supporting multiple currencies and countries.

**Version 2.0** introduces a flexible architecture with three integration methods to suit different use cases.

Refer to the official documentation for further details: [AfribaPay Documentation](https://docs.afribapay.com).

---

## Installation

### Prerequisites

1. **PHP Version**: Ensure you are using PHP 8.1 or higher.
2. **Extensions**: `curl`, `json`, `openssl` enabled



### Steps

1. Clone or download the SDK repository:
  ```bash
   git clone https://github.com/afribapay/afribapay-php-checkout-modal.git
  ```
2. Configure your API credentials in `checkout/customer_config.inc.php` (see Configuration section below)
3. Choose your integration method (see Quick Start below)

---



## Quick Start - 3 Integration Methods



### Method 1: Modal Integration (Recommended)

**Best for:** Quick integration, single payment buttons

```php
<?php
// Include the modal
require_once __DIR__ . '/checkout/modal/afribapay.modal.php';

// Create payment request
$request = new AfribaPayRequest();
$request->amount = 1000;
$request->currency = 'XOF';
$request->country = 'BJ';
$request->order_id = 'ORDER_' . uniqid();

// Create button
$modal = new AfribaPayModal();
echo $modal->createCheckoutButton($request, '💳 Pay Now');
?>
```



### Method 2: Bootstrap Integration

**Best for:** Full control, custom implementations

```php
<?php
// Include bootstrap
require_once __DIR__ . '/checkout/bootstrap.php';

// Initialize SDK
$afribapay = afribapay_init();

// Use SDK features
$countries = $afribapay->fetchCountries('BJ', 'XOF', true);
?>
```



### Method 3: Direct Checkout Page

**Best for:** Hosted payment page

```php
<?php
// Redirect to checkout
header('Location: /checkout/index.php');
?>
```

---



## Usage Guide



### 1. Configure access

Use the `customer_config.inc.php` file in the `checkout` directory to configure API identifiers:

```php
    return [
        // The user identifier for the API, typically a public (non-sensitive) key used to identify the client application.
        'apiUser' => 'your_api_user',

        // The secret key used to authenticate API requests.
        // This key should be kept confidential and never exposed publicly.
        'apiKey' => 'your_secret_key',

        // A unique key assigned to the merchant using the API.
        // It helps link requests to the correct merchant account.
        'merchantKey' => 'your_merchant_key',

        // A unique identifier assigned to the agent (partner or vendor).
        // It is used for tracking operations, transactions, and reporting.
        'agent_id' => 'your_agent_id',

        // Defines the current working environment of the API:
        // Use 'production' for live transactions or 'sandbox' for test mode.
        'environment' => 'sandbox',

        // The language used in API responses and user-facing interfaces.
        // Example: 'fr' for French, 'en' for English.
        'lang' => 'fr',

        // URL that the payment system will notify (via POST) about the result/status of the transaction.
        // This endpoint should handle IPN (Instant Payment Notification) securely.
        'notify_url' => "https://www.example.com/checkout/notify", // Replace this with your own URL
        
        // Relative path from your application to the embedded checkout modal source folder.
        // This is where the SDK modal files are located.
        'checkoutPath'=> '/checkout/', 

        // Indicates whether to use a cache folder to store session/transaction data (recommended for server environments).
        // If false, fallback to client-side cookies.
        'useCacheFolder' => true,

        // Directory path to store cached data if 'useCacheFolder' is true.
        // `sys_get_temp_dir()` returns the system's temporary directory dynamically.
        'cacheDirectory' => sys_get_temp_dir(),

        // Bootstrap CSS used by the modal: 'auto' (public CDNs with browser-side fallback),
        // your own URL, or false if your site already loads Bootstrap 5.
        'bootstrapCss' => 'auto',

    ];
```



### 2. Initialize the MODAL

Use the `AfribaPayModal` class to define a new modal object

```php
$AfribaPayButton = new AfribaPayModal();
```



### 3. Create a Payment Request

Define transaction details using the `AfribapayRequest` object:

```php
$request = new AfribapayRequest();
$request->amount = 100; // Transaction amount
$request->currency = 'XOF'; // Transaction currency
$request->order_id = 'ORDER123'; // Unique order ID
$request->reference_id = 'ref-tfp-bf'; // Reference ID
$request->country = 'BF'; // Transaction country
$request->showCountries = true; // Display available countries for payment 
$request->notify_url = "https://www.example.com/checkout/notify"; // URL that the payment system will notify (via POST) about the result/status of the transaction.
```



### 4. Generate a Checkout Button

Use the `createCheckoutButton` method to generate an HTML button for the payment page:

```php
$buttonHtml = $AfribaPayButton->createCheckoutButton(
    $request, // Payment request object
    'Pay now', // Button text
    '#FF5733', // Button color
    'large' // Button size
);

echo $buttonHtml; // Output the button HTML
```



### 5. Handle Errors

The MODAL provides structured error handling for better debugging:

```php
try {
    // Payment logic here
} catch (AfribaPayException $e) {
    echo "AfribaPayException: " . $e->getMessage();
} catch (Exception $e) {
    echo "UnknownException: " . $e->getMessage();
}
```

---



## Full PHP Example

Access configuration in customer_config.inc.php

```php
<?php
return [
    'apiUser'           => 'pk_15fb8ccc-e2a8-4350-afad-acbf224f2e64',
    'apiKey'            => 'sk_NA24xhNko7N96XJQZzBd337W33l5Ff5q4jSv1907m',
    'merchantKey'       => 'mk_Dv2c9Us240920061620',
    'agent_id'          => 'APM31923613',
    'environment'       => 'sandbox',
    'lang'              => 'fr',
    'notify_url'        => "https://www.examplee.com/checkout/notify",
    'checkoutPath'      => '/checkout/',
    'useCacheFolder'    => true,
    'cacheDirectory'    => sys_get_temp_dir()
];
?>
```

Main code

```php
<?php
require 'path_to_modal/afribapay.modal.php';

try {
    $AfribaPayButton = new AfribaPayModal();
    $request = new AfribapayRequest();
    $request->amount = 150;
    $request->currency = 'XOF';
    $request->order_id = 'ORDER123';
    $request->reference_id = 'ref-tfp-bf';
    $buttonHtml = $AfribaPayButton->createCheckoutButton(
        $request, 
        'Payer now', 
        '#FF5733', 
        'large'
    );
    echo $buttonHtml;
} catch (AfribaPayException $e) {
    echo "AfribaPayException: " . $e->getMessage();
} catch (Exception $e) {
    echo "UnknownException: " . $e->getMessage();
}
```

---



### Running the examples locally

Start PHP's built-in server from the SDK folder:

```bash
cd afp-modal && php -S localhost:8000
```

Then open `http://localhost:8000/examples/`.

The server can be started from **any folder** (e.g. a parent folder, or XAMPP/WAMP/MAMP `htdocs`) and on **any OS** (macOS, Linux, Windows): the URLs of `checkout/` and `examples/` are detected automatically. If `checkout/` is not reachable from the web root (e.g. outside `public/`), set `checkoutPath` manually in `customer_config.inc.php`.

---



## Supported Currencies

The MODAL supports the following currencies:


| Currency Code | Description               |
| ------------- | ------------------------- |
| **XOF**       | West African CFA Franc    |
| **XAF**       | Central African CFA Franc |
| **CDF**       | Congolese Franc           |
| **GNF**       | Guinean Franc             |
| **KES**       | Kenyan Shilling           |
| **MWK**       | Malawian Kwacha           |
| **RWF**       | Rwandan Franc             |
| **SLE**       | Sierra Leonean Leone      |
| **UGX**       | Ugandan Shilling          |
| **ZMW**       | Zambian Kwacha            |


---



## Supported Countries

The MODAL supports payments in the following countries:


| Country Code | Currency Code | Country Name        |
| ------------ | ------------- | ------------------- |
| **BJ**       | XOF           | Benin               |
| **BF**       | XOF           | Burkina Faso        |
| **CI**       | XOF           | Côte d’Ivoire       |
| **GW**       | XOF           | Guinea-Bissau       |
| **ML**       | XOF           | Mali                |
| **NE**       | XOF           | Niger               |
| **SN**       | XOF           | Senegal             |
| **TG**       | XOF           | Togo                |
| **CM**       | XAF           | Cameroon            |
| **CF**       | XAF           | Central African Rep |
| **TD**       | XAF           | Chad                |
| **CG**       | XAF           | Republic of Congo   |
| **GQ**       | XAF           | Equatorial Guinea   |
| **GA**       | XAF           | Gabon               |
| **CD**       | CDF           | DR Congo            |
| **GN**       | GNF           | Guinea              |
| **KE**       | KES           | Kenya               |
| **MW**       | MWK           | Malawi              |
| **RW**       | RWF           | Rwanda              |
| **SL**       | SLE           | Sierra Leone        |
| **UG**       | UGX           | Uganda              |
| **ZM**       | ZMW           | Zambia              |


---



## API Reference



### `customer_config.inc.php`



#### File properties:


| Parameter        | Type   | Description                                                                                    |
| ---------------- | ------ | ---------------------------------------------------------------------------------------------- |
| `apiUser`        | String | User identifier for the API                                                                    |
| `apiKey`         | String | Secret key for authentication                                                                  |
| `agent_id`       | String | Unique identifier for the agent                                                                |
| `merchantKey`    | String | Specific key for the merchant account                                                          |
| `environment`    | String | 'sandbox' or 'production' for API environment                                                  |
| `lang`           | String | Response language ('en', 'fr', etc.)                                                           |
| `checkoutPath`   | String | Relative path to join seamless source directory from your project                              |
| `useCacheFolder` | String | Use cache directory or not. If ‘true’, caches are used, otherwise cookies are used.            |
| `cacheDirectory` | String | Cache directory, by default, cacheDirectory takes the server's temporary directory             |
| `notify_url`     | String | URL that the payment system will notify (via POST) about the result/status of the transaction. |
| `bootstrapCss`   | String | false                                                                                          |




### `AfribapayRequest`



#### Properties:


| Property       | Type   | Description                      |
| -------------- | ------ | -------------------------------- |
| `amount`       | Float  | Payment amount                   |
| `currency`     | String | Payment currency                 |
| `description`  | String | Payment description              |
| `order_id`     | String | Unique order identifier          |
| `reference_id` | String | Reference ID for reconciliation  |
| `country`      | String | Country code for the transaction |
| `company`      | String | Name of the initiating company   |
| `checkou_name` | String | Title for the checkout page      |


---



## Project Structure

```
afribapay-php-checkout-seamless-modal/
├── checkout/
│   ├── bootstrap.php              # SDK entry point
│   ├── index.php                  # Checkout page
│   ├── checkout.php               # Checkout view
│   ├── customer_config.inc.php    # Your configuration
│   ├── modal/
│   │   ├── afribapay.modal.php    # Modal class
│   │   ├── AfribapayClass.php     # API handler
│   │   └── config.inc.php         # Internal config
│   ├── Helpers/
│   │   └── Helpers.php            # Helper functions
│   └── Language/
│       ├── en/                    # English translations
│       └── fr/                    # French translations
│   
├── examples/
│   ├── index.php                  # Examples navigation
│   ├── simple-button.php          # Basic example
│   ├── multiple-products.php      # Shop example
│   ├── with-bootstrap.php         # Advanced example
│   └── ...
└── README.md                      # This file
```

---



## Payment integrity

The payment parameters set on the server (`amount`, `currency`, `order_id`, URLs...) are **signed** (HMAC-SHA256, key derived from your `apiKey`) when the button is generated:

- if the payer modifies them in the browser, the checkout answers **HTTP 400** and no payment is created;
- at payment time, the amount and currency are read **from the server-side transaction**, never from the browser. The payer can only enter them when you leave them empty ("free amount" mode).

**You must still verify the notification** sent to your `notify_url` and compare the amount and currency with your order before marking it as paid (mandatory in "free amount" mode): see *Handling notifications* below.

## Handling notifications (`notify_url`)

When a transaction reaches a final status (`SUCCESS` or `FAILED`), AfribaPay sends an HTTP POST (JSON) to your `notify_url`, signed in the `AFRIBAPAY_SIGN` header (HMAC-SHA256 of the raw body with your `apiKey`). The SDK verifies it for you:

```php
<?php
require_once 'path/to/checkout/notification.php';

try {
    $payment = AfribaPayNotification::verify();          // POST, signature, JSON, required fields, SUCCESS/FAILED
    // $payment = AfribaPayNotification::verify(confirmWithApi: true); // + check with the Status API
} catch (AfribaPayNotificationException $e) {
    AfribaPayNotification::acknowledge($e->getHttpCode(), $e->getMessage()); // 405, 400, 403 or 503
    exit;
}

// In a database transaction, with the order row locked (SELECT ... FOR UPDATE)
$order = findOrder($payment->orderId);                    // unknown order: answer 400, do nothing
if (alreadyProcessed($payment->transactionId)) {          // duplicate notification: answer 200, do nothing
    AfribaPayNotification::acknowledge();
    exit;
}
if ($payment->isSuccessful()
    && $payment->matchesOrder($order->id, $order->amount, $order->currency, $order->country)) {
    $order->markAsPaid($payment->transactionId);          // never overwrite an already paid order
} elseif ($payment->isFailed()) {
    $order->markAsFailed($payment->transactionId);
}
AfribaPayNotification::acknowledge();                     // HTTP 200 only after the commit
```

- `$payment->amount` is `original_amount` (amount requested, before fees). `matchesOrder()` returns `false` if it is missing.
- Notifications may be delivered **several times, even simultaneously**: use `transactionId` with a lock or a unique constraint to process each one only once.
- One `order_id` can have **several transactions**: after a failure the payer can retry the same order. Key your processing on `transactionId`, never mark a paid order as failed, and flag a second `SUCCESS` on an already paid order for refund. In the same browser, the checkout refuses a new payment once a payment of the order has succeeded (`This order has already been paid.`).
- A verified final state must never be overwritten: flag conflicting statuses for manual reconciliation.
- The retry policy of AfribaPay is not documented: keep your orders pending until a final status, and reconcile with the Transaction Status API if a notification never arrives.
- `notify_url` must be a public **HTTPS** URL.
- A complete example is in `examples/11-notify-handler.php`, and `tests/notify_handler_test.php <url>` replays the test scenarios of the official guide against it.



## Troubleshooting



### Common Issues


| Issue                                                                        | Solution                                                                                                                                        |
| ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `customer_config.inc.php not found`                                          | Verify the file path and permissions                                                                                                            |
| `Invalid authentication credentials`                                         | Check your API keys in config file                                                                                                              |
| `Invalid baseUrl format`                                                     | Ensure the API URL is correct in config                                                                                                         |
| Modal doesn't open                                                           | Check browser console for JavaScript errors                                                                                                     |
| `Invalid payment parameters: the payment data was modified` (HTTP 400)       | The signed payment data was changed in the browser, or `apiKey`/`merchantKey` changed between the page display and the payment: reload the page |
| Payment error messages are generic ("The payment could not be processed...") | In `production`, technical details are only written to the PHP error log (`error_log`); `sandbox` shows them to help you integrate              |
| `Unknown payment for this session.` (HTTP 403) on a status check             | Only payments started in the current checkout session can be checked; reload the page and pay again                                             |
| CSS not loading                                                              | Verify `checkoutPath` in config file                                                                                                            |
| Bootstrap loaded twice / CSP blocks inline `onerror`                         | Set `bootstrapCss` to `false` or to your own URL in `customer_config.inc.php`                                                                   |




### Debug Mode

Enable detailed error reporting:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
```

---



## Support & Contact

- Email: [help@afribapay.com](mailto:support@afribapay.com)
- Documentation: [https://docs.afribapay.com](https://docs.afribapay.com)
- 🌐 Website: [https://afribapay.com](https://afribapay.com)

---



## 📄 License

This SDK is licensed under the [MIT License](LICENSE).

---

