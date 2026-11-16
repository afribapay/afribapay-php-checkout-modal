<?php

define('CACHE_PATH_FILE', TransactionConfig::getCacheDirectory(). '/afribapay-countries.json');
define('COUNTRIES_CACHE_DURATION', 3600 * 72);

class AfribapayClass {
    private static $config = [];
    private $token;
    private $tokenExpiration;

    public $formParameters = [];

    public function __construct() {
        self::$config = TransactionConfig::getConfigurations();
        $this->formParameters = TransactionConfig::getParameters();
        modal_afp_load_language($this->formParameters['lang'] ?? 'en');

        $this->token = null;
        $this->tokenExpiration = 0;
    }

    public function fetchCountries($country, $currency, $showCountries) {
        try {
            $listOfCountries = $this->countries();
            if (isset(self::$config['countries']) && is_array(self::$config['countries']) && !empty(self::$config['countries'])) {
                $listOfCountries = array_filter($listOfCountries, function ($key) {
                    return in_array($key, self::$config['countries']);
                }, ARRAY_FILTER_USE_KEY);
            }
            $defaultCountry  = $listOfCountries[$country] ?? null;
            $countriesToShow = $defaultCountry && !$showCountries ? [$defaultCountry] : $listOfCountries;
            if($currency){
                $optimal = [];
                $currencies = in_array($currency, ['XOF', 'XAF']) ? ['XAF', 'XOF'] : [$currency];
                foreach($countriesToShow as $country){
                    foreach ($currencies as $current) {
                        if(array_key_exists($current, $country['currencies'])){
                            $row = [];
                            $row['country_code'] = $country['country_code'];
                            $row['country_name'] = $country['country_name'];
                            $row['country_flag'] = $country['country_flag'];
                            $row['prefix']       = $country['prefix'];
                            $row['taxes']        = $country['taxes'];
                            $row['currencies'][$current] = $country['currencies'][$current];
                            $optimal[$country['country_code']] = $row;
                        }
                    }
                }
                $countriesToShow = $optimal;
            }
            uasort($countriesToShow, function ($a, $b) {
                return strcmp($a['country_name'], $b['country_name']);
            });
            return $countriesToShow;
        } catch (Exception $error) {
            $this->afpDebug("Error fetching countries: " . $error->getMessage());
            return ['error' => 'Failed to fetch countries', 'message' => modal_afp_public_error($error)];
        }
    }

    public function getCheckout($paymentDetails) {
        try {
            $getCheckout = $this->checkout($paymentDetails);
            echo json_encode($getCheckout);
        } catch (Exception $error) {
            $this->afpDebug("Error during payment initialization: " . $error->getMessage());
            echo json_encode(['error' => 'An error occurred during payment initialization.', 'message' => modal_afp_public_error($error)]);
        }
    }

    public function sendWalletOtp($walletDetails) {
        try {
            $sendWalletOtp = $this->walletOtp($walletDetails['data'] ?? throw new Exception('no wallet data'));
            echo json_encode($sendWalletOtp);
        } catch (Exception $error) {
            $this->afpDebug("Error during payment initialization: " . $error->getMessage());
            echo json_encode(['error' => 'An error occurred during payment initialization.', 'message' => modal_afp_public_error($error)]);
        }
    }

    // $paymentId: the transaction_id of the payment (an order_id is still accepted)
    public function getStatus($paymentId) {
        try {
            if (is_string($paymentId) && TransactionConfig::isKnownPaymentTransactionId($paymentId)) {
                $lookup = 'transaction_id';
            } elseif (is_string($paymentId) && TransactionConfig::isKnownOrderId($paymentId)) {
                $lookup = 'order_id';
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Unknown payment for this session.']);
                return;
            }
            $getStatus = $this->status($paymentId, $lookup);
            echo json_encode($getStatus);
        } catch (Exception $error) {
            $this->afpDebug("Error during payment initialization: " . $error->getMessage());
            echo json_encode(['error' => 'An error occurred during payment initialization.', 'message' => modal_afp_public_error($error)]);
        }
    }

    private function getCache() {
        if (file_exists(CACHE_PATH_FILE)) {
            $content = file_get_contents(CACHE_PATH_FILE);
            return json_decode($content, true) ?? [];
        }
        return [];
    }

    private function saveCache(array $cache) {
        file_put_contents(CACHE_PATH_FILE, json_encode($cache, JSON_PRETTY_PRINT));
    }

    private function apiUrl(string $path): string {
        return rtrim(self::$config['baseUrl'], '/') . '/' . ltrim($path, '/');
    }

    private function createCurlHandle($url, $isPost = false, $headers = []) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => $isPost,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        return $ch;
    }

    private function executeRequest($ch) {
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if ($curlErrno !== 0 || $response === false) {
            $host = parse_url((string)$url, PHP_URL_HOST) ?: (string)$url;
            throw new Exception("Network error while contacting {$host}: {$curlError} (cURL error {$curlErrno}).");
        }

        return ['httpCode' => $httpCode, 'response' => json_decode($response, true)];
    }

    private function checkConfigParam(): void {
        $requiredKeys = ['baseUrl', 'apiKey', 'apiUser', 'merchantKey'];
        if (!is_array(self::$config)) {
            throw new InvalidArgumentException('The configuration must be an array.');
        }
        $missingKeys = [];
        $emptyKeys = [];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, self::$config)) {
                $missingKeys[] = $key;
            } elseif (self::$config[$key] === null || trim((string)self::$config[$key]) === '') {
                $emptyKeys[] = $key;
            }
        }
        if (!empty($missingKeys) || !empty($emptyKeys)) {
            $errorMessages = [];
            if (!empty($missingKeys)) {
                $errorMessages[] = "Missing key(s) : " . implode(', ', $missingKeys);
            }
            if (!empty($emptyKeys)) {
                $errorMessages[] = "Empty or null key(s) : " . implode(', ', $emptyKeys);
            }
            if(!empty($errorMessages)){
                throw new InvalidArgumentException(implode(' | ', $errorMessages) . ' in configuration data.');
            }
        }
        if (!$this->isUrlStrictlyValid(self::$config['baseUrl'])) {
            throw new Exception("Invalid baseUrl format.");
        }
    }

    function isUrlStrictlyValid(string $url): bool {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'], true)) {
            return false;
        }
        if (!isset($parsedUrl['host']) || !preg_match('/^[a-z0-9\-\.]+\.[a-z]{2,}$/i', $parsedUrl['host'])) {
            return false;
        }
        if (isset($parsedUrl['port']) && ($parsedUrl['port'] < 1 || $parsedUrl['port'] > 65535)) {
            return false;
        }
        return true;
    }

    private function authenticate() {
        try{
            $this->checkConfigParam();
            if ($this->token && time() < $this->tokenExpiration) {
                return $this->token;
            }
            $credentials = base64_encode(self::$config['apiUser'] . ':' . self::$config['apiKey']);
            $ch = $this->createCurlHandle(
                $this->apiUrl('/v1/token'),
                true,
                [
                    'Authorization: Basic ' . $credentials,
                    'Content-Type: application/json'
                ]
            );
            $result = $this->executeRequest($ch);
            if (isset($result['response']['data']['access_token'])) {
                $this->token = $result['response']['data']['access_token'];
                $expires_in = $result['response']['data']['expires_in'];
                $this->tokenExpiration = time() + $expires_in;
                return $this->token;
            }
            $apiMessage = $result['response']['message'] ?? $result['response']['error'] ?? null;
            if (in_array($result['httpCode'], [401, 403], true)) {
                throw new Exception("Invalid authentication credentials (HTTP {$result['httpCode']}). Check apiUser, apiKey and environment in customer_config.inc.php."
                    . ($apiMessage ? " API message: " . (is_string($apiMessage) ? $apiMessage : json_encode($apiMessage)) : ''));
            }
            throw new Exception("Authentication failed: unexpected API response (HTTP {$result['httpCode']})."
                . ($apiMessage ? " API message: " . (is_string($apiMessage) ? $apiMessage : json_encode($apiMessage)) : ''));
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    private function refreshTokenIfNeeded() {
        try{
            if (!$this->token || time() >= $this->tokenExpiration) {
                return $this->authenticate();
            }
            return $this->token;
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    public function sdkFirstAuthentication() {
        try{
            $this->token = null;
            $this->tokenExpiration = 0;
            return $this->authenticate();
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }
    
    private function countries() {
        if(TransactionConfig::isUseCache()){
            $cache = $this->getCache();
            $currentTime = time();
            if (isset($cache['countries']) && ($currentTime - $cache['countries']['timestamp'] < COUNTRIES_CACHE_DURATION)) {
                return $cache['countries']['data'];
            }
            $listOfCountries = $this->fetchCountriesFromApi();
            $cache['countries'] = [
                'data' => $listOfCountries,
                'timestamp' => $currentTime
            ];
            $this->saveCache($cache);
            return $listOfCountries;
        } else {
            return $this->fetchCountriesFromApi();
        }
    }

    private function fetchCountriesFromApi() {
        try {
            $token = $this->refreshTokenIfNeeded();
            if (!$token) {
                return ['httpCode' => 401, 'response' => ['error' => 'Authentication failed']];
            }

            $ch = $this->createCurlHandle(
                $this->apiUrl('/v1/countries'),
                false,
                [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ]
            );

            $response = $this->executeRequest($ch);
            return $response['response']['data'] ?? [];

        } catch (Exception $e) {
            throw $e;
        }
    }

    // Merchant values always win; the browser may only fill them when left empty (free amount)
    private function lockedPaymentValues(array $data): array {
        if (!TransactionConfig::hasTransaction()) {
            throw new Exception("Payment session expired or invalid. Please reload the page and try again.");
        }
        $values = [];

        $merchantAmount = $this->formParameters['amount'] ?? null;
        $browserAmount  = isset($data['amount']) && is_numeric($data['amount']) ? floor($data['amount']) : null;
        if (!empty($merchantAmount)) {
            if ($browserAmount !== null && (int)$browserAmount !== (int)floor($merchantAmount)) {
                throw new Exception("Invalid payment amount.");
            }
            $values['amount'] = (int)floor($merchantAmount);
        } else {
            if ($browserAmount === null || $browserAmount < 1) {
                throw new Exception("Invalid payment amount.");
            }
            $values['amount'] = (int)$browserAmount;
        }

        $merchantCurrency = $this->formParameters['currency'] ?? null;
        $browserCurrency  = isset($data['currency']) && is_string($data['currency']) ? strtoupper(trim($data['currency'])) : null;
        if (!empty($merchantCurrency)) {
            if ($browserCurrency !== null && $browserCurrency !== '' && $browserCurrency !== strtoupper($merchantCurrency)) {
                throw new Exception("Invalid payment currency.");
            }
            $values['currency'] = strtoupper($merchantCurrency);
        } else {
            if (empty($browserCurrency)) {
                throw new Exception("Invalid payment currency.");
            }
            $values['currency'] = $browserCurrency;
        }

        return $values;
    }

    private function checkout($data) {
        try {
            $this->checkConfigParam();
            $data = is_string($data) ? (json_decode($data, true) ?: []) : (is_array($data) ? $data : []);
            $data = array_merge($data, $this->lockedPaymentValues($data));
            $this->assertOrderNotAlreadyPaid();
            $token = $this->refreshTokenIfNeeded();
            if (!$token) {
                return ['httpCode' => 401, 'response' => ['error' => 'Authentication failed']];
            }
            $timestamp = time();
            $random = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 4)), 0, 4);
            $notify_url = $this->formParameters['notify_url'] ?? self::$config['notify_url'] ?? '';
            if (!empty($notify_url)) {
                if (!$this->isUrlStrictlyValid($notify_url)) {
                    throw new Exception("Invalid notification URL format.");
                }
                $parsedUrl = parse_url($notify_url);
                if (isset($parsedUrl['host']) && strpos($parsedUrl['host'], 'example.com') !== false) {
                    throw new Exception("The notify_url cannot point to example.com. Please use a valid URL.");
                }
            }
            $body = [
                "operator"     => $data['operator'],
                "country"      => $data['country'],
                "phone_number" => $data['phone'],
                "amount"       => $data['amount'],
                "currency"     => $data['currency'],
                "order_id"     => $this->formParameters['order_id'] ?? 'webID' . implode('', [$data['country'], $data['currency'], $data['amount'], $data['phone'], $random , $timestamp]),
                "merchant_key" => self::$config['merchantKey'],
                "reference_id" => $this->formParameters['reference_id'] ?? $data['reference_id'] ?? 'webcheckout-ref-' . implode('-', [$data['country'], $data['currency'], $data['amount']]),
                "lang"         => self::$config['lang'],
                "notify_url"   => $notify_url
            ];
        
            if (!empty($data['otp_code'])) {
                $body["otp_code"] = $data['otp_code'];
            }

            $ch = $this->createCurlHandle(
                $this->apiUrl('/v1/pay/payin'),
                true,
                [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ]
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

            $result = $this->executeRequest($ch);
            TransactionConfig::rememberPayment(
                (string)($result['response']['data']['order_id'] ?? $body['order_id']),
                isset($result['response']['data']['transaction_id']) ? (string)$result['response']['data']['transaction_id'] : null
            );
            return $result;
        } catch (AfribaPayPublicException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // A previous payment of this order may have succeeded (late operator confirmation, reload after a success)
    private function assertOrderNotAlreadyPaid(): void {
        foreach (TransactionConfig::paymentTransactionIds() as $paymentTransactionId) {
            $result = $this->status($paymentTransactionId, 'transaction_id');
            if (($result['response']['data']['status'] ?? null) === 'SUCCESS') {
                throw new AfribaPayPublicException(modal_afp_lang('Ck.ORDER_ALREADY_PAID'));
            }
        }
    }

    private function walletOtp($data) {
        try {
            $this->checkConfigParam();
            $data = is_string($data) ? (json_decode($data, true) ?: []) : (is_array($data) ? $data : []);
            $data = array_merge($data, $this->lockedPaymentValues($data));

            $token = $this->refreshTokenIfNeeded();
            if (!$token) {
                return ['httpCode' => 401, 'response' => ['error' => 'Authentication failed']];
            }
            $body = [
                "operator"     => $data['operator'],
                "country"      => $data['country'],
                "phone_number" => $data['phone'],
                "amount"       => floor($data['amount']),
                "currency"     => $data['currency'],
                "merchant_key" => self::$config['merchantKey']
            ];
            $ch = $this->createCurlHandle(
                $this->apiUrl('/v1/pay/otp'),
                true,
                [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ]
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            return $this->executeRequest($ch);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // By order_id the API returns the FIRST transaction of the order: follow a payment by its transaction_id
    private function status(string $id, string $lookup = 'order_id') {
        try{
            $token = $this->refreshTokenIfNeeded();
            if (!$token) {
                return ['httpCode' => 401, 'response' => ['error' => 'Authentication failed']];
            }
            $ch = $this->createCurlHandle(
                $this->apiUrl('/v1/status?' . http_build_query([$lookup === 'transaction_id' ? 'transaction_id' : 'order_id' => $id])),
                false,
                [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ]
            );
            return $this->executeRequest($ch);
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    private function afpDebug($message){
        error_log($message);
    }

}
