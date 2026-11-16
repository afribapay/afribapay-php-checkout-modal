<?php

define("CACHE_EXPIRY", 900); // 15 minutes
define("DATA_COOKIE_NAME", 'afpData');
define("TRANSID_COOKIE_NAME", 'afpId');
define("CACHE_EXTENSION", '.afp.cache');
define("CUSTOMER_CONFIG_PATH", dirname(__FILE__, 2) . '/customer_config.inc.php');

class TransactionConfig {
    private static $configurations = [];
    private static $parameters = [];
    private static $transactionId = null;
    private static $memoryMode = false;
    private static $getCacheDirectory = null;
 
    private static function isNewTransaction(): bool {
        $isNew = (!empty($_POST['baseUrl']) && !empty($_POST['seamlessModalId']));
        return $isNew;
    }

    // $_COOKIE may be empty or badly parsed under some frameworks (CodeIgniter)
    private static function parseCookieFromHeader(string $cookieName): ?string {
        if (!isset($_SERVER['HTTP_COOKIE']) || empty($_SERVER['HTTP_COOKIE'])) {
            return null;
        }
        
        $cookies = explode('; ', $_SERVER['HTTP_COOKIE']);
        foreach ($cookies as $cookie) {
            if (strpos($cookie, '=') === false) {
                continue;
            }
            
            $parts = explode('=', $cookie, 2);
            $name = trim($parts[0]);
            $value = isset($parts[1]) ? trim($parts[1]) : '';
            
            if ($name === $cookieName) {
                return $value;
            }
        }
        return null;
    }

    public static function initialize(): void {
        self::$getCacheDirectory = self::getCacheDirectory();
        $cookieId = self::parseCookieFromHeader(TRANSID_COOKIE_NAME);
        // Used as a cache file name
        self::$transactionId = self::isValidTransactionId($cookieId) ? $cookieId : null;
        self::$memoryMode = self::memoryMode();
        
        if (self::isNewTransaction()) {

            self::verifySignature($_POST);
            $previousParameters = self::$transactionId ? (self::loadCachedConfig(self::$transactionId, self::$memoryMode)['parameters'] ?? []) : [];
            self::clearCacheAndCookies(self::$memoryMode);
            self::$transactionId = bin2hex(random_bytes(16));
            self::$configurations = [
                "baseUrl"     => !empty($_POST['baseUrl']) ? urldecode($_POST['baseUrl']) : null
            ];
            self::$parameters = [
                'country'       => !empty($_POST['country']) ? urldecode($_POST['country']) : null,
                'currency'      => !empty($_POST['currency']) ? urldecode($_POST['currency']) : null,
                'amount'        => (!empty($_POST['amount']) && $_POST['amount'] > 0) ? floor($_POST['amount']) : null,
                'order_id'      => !empty($_POST['order_id']) ? urldecode($_POST['order_id']) : null,
                'reference_id'  => !empty($_POST['reference_id']) ? urldecode($_POST['reference_id']) : null,
                'showCountries' => isset($_POST['showCountries']) ? $_POST['showCountries'] : true,
                'notify_url'    => isset($_POST['notify_url']) ? $_POST['notify_url'] : null,

                // TODO: add for seamless
                'checkout_name' => !empty($_POST['checkout_name']) ? urldecode($_POST['checkout_name']) : null,
                'company'       => !empty($_POST['company']) ? urldecode($_POST['company']) : null,
                'description'   => !empty($_POST['description']) ? urldecode($_POST['description']) : null,
                'weblogo'       => !empty($_POST['logo_url']) ? urldecode($_POST['logo_url']) : null,
                'return_url'    => !empty($_POST['return_url']) ? urldecode($_POST['return_url']) : null,
                'cancel_url'    => !empty($_POST['cancel_url']) ? urldecode($_POST['cancel_url']) : null,

            ];

            // Reloading the checkout page re-posts the signed form: keep the payments already made for this order
            if (!empty(self::$parameters['order_id']) && ($previousParameters['order_id'] ?? null) === self::$parameters['order_id']) {
                self::$parameters['payment_order_ids']       = $previousParameters['payment_order_ids'] ?? [];
                self::$parameters['payment_transaction_ids'] = $previousParameters['payment_transaction_ids'] ?? [];
            }

            if(!(new AfribapayClass())->sdkFirstAuthentication()){
                return;
            } 

            $dataToCache = ['configurations' => self::$configurations, 'parameters' =>  self::$parameters];
            if (!self::saveCachedConfig($dataToCache, self::$transactionId, self::$memoryMode)) {
            }
            
            self::setSecureCookie(TRANSID_COOKIE_NAME, self::$transactionId, time() + CACHE_EXPIRY);
        } else {

            if (!self::$transactionId) {
                return;
            }
            $cachedConfig = self::loadCachedConfig(self::$transactionId, self::$memoryMode);
            if ($cachedConfig) {
                self::$configurations = $cachedConfig['configurations'] ?? [];
                self::$parameters = $cachedConfig['parameters'] ?? [];
            } else {
            }
        }
    }

    private static function loadCachedConfig(string $encryptionKey, bool $useCache = false): ?array {
        if ($useCache && $encryptionKey !== '') {
            $cacheFile = self::$getCacheDirectory . '/' . $encryptionKey . CACHE_EXTENSION;
            if (file_exists($cacheFile)) {
                $encryptedData = file_get_contents($cacheFile);
            } else {
                return null;
            }
        } else {
            if (isset($_COOKIE[DATA_COOKIE_NAME]) && is_string($_COOKIE[DATA_COOKIE_NAME])) {
                $encryptedData = $_COOKIE[DATA_COOKIE_NAME];
            } else {
                return null;
            }
        }
        $configData = self::decryptData($encryptedData, $encryptionKey);
        if ($configData === false) {
            return null;
        }
        $config = json_decode($configData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $config;
    }

    private static function saveCachedConfig(array $config, string $encryptionKey, bool $useCache = false): bool {
        $configData = json_encode($config);
        if ($configData === false) {
            return false;
        }
        $encryptedData = self::encryptData($configData, $encryptionKey);
        if ($encryptedData === false) {
            return false;
        }
        if ($useCache && $encryptionKey !== '') {
            $cacheDir = self::$getCacheDirectory;
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
            $cacheFile = $cacheDir . '/' . $encryptionKey . CACHE_EXTENSION;
            return file_put_contents($cacheFile, $encryptedData) !== false;
        } else {
            self::setSecureCookie(DATA_COOKIE_NAME, $encryptedData, time() + CACHE_EXPIRY);
            return true;
        }
    }

    // The browser only holds the transaction id, never the key
    private static function cacheKey(string $transactionId): string {
        $apiKey = (string)(self::extraData('config')['apiKey'] ?? '');
        return hash_hmac('sha256', 'afp-cache|' . $transactionId, $apiKey, true);
    }

    // AES-256-GCM: any tampering makes decryption fail
    private static function encryptData(string $data, string $transactionId): string|false {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', self::cacheKey($transactionId), OPENSSL_RAW_DATA, $iv, $tag);
        if ($encrypted === false) {
            return false;
        }
        return base64_encode($iv . $tag . $encrypted);
    }

    private static function decryptData(string $encryptedData, string $transactionId): string|false {
        $data = base64_decode($encryptedData, true);
        if ($data === false || strlen($data) < 29) {
            return false;
        }
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        return openssl_decrypt(substr($data, 28), 'aes-256-gcm', self::cacheKey($transactionId), OPENSSL_RAW_DATA, $iv, $tag);
    }

    private static function isValidTransactionId(?string $id): bool {
        return is_string($id) && preg_match('/^[a-f0-9]{32}$/', $id) === 1;
    }

    private static function setSecureCookie(string $name, string $value, int $expires): void {
        setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => modal_afp_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearCacheAndCookies(bool $useCache = false): void {
        $cookiesToDelete = [TRANSID_COOKIE_NAME, DATA_COOKIE_NAME];
        foreach ($cookiesToDelete as $cookieName) {
            if (isset($_COOKIE[$cookieName])) {
                unset($_COOKIE[$cookieName]);
                self::setSecureCookie($cookieName, '', time() - CACHE_EXPIRY);
            }
        }
        if($useCache){
            $filePath = self::$getCacheDirectory . "/".self::$transactionId. CACHE_EXTENSION;
            if (self::isValidTransactionId(self::$transactionId) && file_exists($filePath)) {
                unlink($filePath);
            }
            self::cleanExpiredCaches();
        }

    }

    private static function verifySignature(array $post): void {
        $config = self::extraData('config');
        $signature = $post['afp_signature'] ?? '';
        $expected = modal_afp_sign_payload($post, (string)$config['apiKey'], (string)$config['merchantKey']);
        if (!is_string($signature) || !hash_equals($expected, $signature)) {
            throw new AfribaPaySignatureException("Invalid payment parameters: the payment data was modified or is incomplete.");
        }
    }

    public static function hasTransaction(): bool {
        return !empty(self::$parameters);
    }

    // The status action only accepts the payments (order_id / transaction_id) made in this transaction
    public static function rememberPayment(string $orderId, ?string $paymentTransactionId): void {
        if (!self::hasTransaction() || !self::isValidTransactionId(self::$transactionId)) {
            return;
        }
        $orders = self::$parameters['payment_order_ids'] ?? [];
        $payments = self::$parameters['payment_transaction_ids'] ?? [];
        $isNewOrder = !in_array($orderId, $orders, true);
        $isNewPayment = $paymentTransactionId !== null && $paymentTransactionId !== '' && !in_array($paymentTransactionId, $payments, true);
        if ($isNewOrder || $isNewPayment) {
            if ($isNewOrder) {
                $orders[] = $orderId;
                self::$parameters['payment_order_ids'] = array_slice($orders, -10);
            }
            if ($isNewPayment) {
                $payments[] = $paymentTransactionId;
                self::$parameters['payment_transaction_ids'] = array_slice($payments, -10);
            }
            self::saveCachedConfig(
                ['configurations' => self::$configurations, 'parameters' => self::$parameters],
                self::$transactionId,
                self::$memoryMode
            );
        }
    }

    public static function isKnownPaymentTransactionId(string $paymentTransactionId): bool {
        return in_array($paymentTransactionId, self::$parameters['payment_transaction_ids'] ?? [], true);
    }

    public static function paymentTransactionIds(): array {
        return self::$parameters['payment_transaction_ids'] ?? [];
    }

    public static function isKnownOrderId(string $orderId): bool {
        return in_array($orderId, self::$parameters['payment_order_ids'] ?? [], true);
    }

    public static function getConfigurations(): array {
        return array_merge(self::extraData('config'), self::$configurations);
    }

    public static function getParameters(): array {
        return array_merge(self::$parameters, self::extraData('params'));
    }

    public static function getCacheDirectory(): string {
        return self::extraData('cache')['cacheDirectory'];
    }

    public static function isUseCache():bool {
        return self::$memoryMode;
    }

    protected static function extraData(string $param): array {
        $customer_config_path = CUSTOMER_CONFIG_PATH;
        if (!file_exists($customer_config_path)) {
            throw new AfribaPayException("The customer_config.inc.php file was not found at: " . $customer_config_path);
        }
        $params = require $customer_config_path;
        if (!is_array($params)) {
            throw new AfribaPayException("Invalid customer_config.inc.php file");
        }
        return match ($param) { 
            'config' => [
                'apiUser'     => $params['apiUser'] ?? '',
                'apiKey'      => $params['apiKey'] ?? '',
                'merchantKey' => $params['merchantKey'] ?? '',
                'lang'        => $params['lang'] ?? 'fr',
                'notify_url'  => $params['notify_url'] ?? '',
                'baseUrl'     => modal_afp_resolve_base_url($params['environment'] ?? 'sandbox'),
            ],
            'params' => [
                'agent_id'      => $params['agent_id'] ?? '',
                'lang'          => $params['lang'] ?? 'fr',
                'checkoutPath'  => $params['checkoutPath'] ?? '',
            ],
            'cache' => [
                'cacheDirectory' => $params['cacheDirectory'] ?? sys_get_temp_dir(),
            ],
            default => throw new AfribaPayException("Invalid parameter requested in extraData"),
        };
    }

    protected static function memoryMode(): bool {
        $customer_config_path = CUSTOMER_CONFIG_PATH;
        if (!file_exists($customer_config_path)) {
            throw new AfribaPayException("The customer_config.inc.php file was not found at: " . $customer_config_path);
        }
        $params = require $customer_config_path;
        if (!is_array($params)) {
            throw new AfribaPayException("Invalid customer_config.inc.php file");
        }
        $useCacheFolder = $params['useCacheFolder'] ?? false;
        return $useCacheFolder === true || $useCacheFolder === 'true';
    }

    private static function cleanExpiredCaches(): void {
        foreach (glob(self::$getCacheDirectory . "/*".CACHE_EXTENSION) as $file) {
            if (filemtime($file) < time() - CACHE_EXPIRY) {
                unlink($file);
            }
        }
    }
}
