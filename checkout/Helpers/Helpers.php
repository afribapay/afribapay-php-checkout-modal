<?php

if (!class_exists('AfribaPayException')) {
    class AfribaPayException extends Exception {}
}

// Payment parameters modified in the browser (signature mismatch)
// Message intended for the payer: shown even in production
if (!class_exists('AfribaPayPublicException')) {
    class AfribaPayPublicException extends AfribaPayException {}
}

if (!class_exists('AfribaPaySignatureException')) {
    class AfribaPaySignatureException extends AfribaPayException {}
}

if (!function_exists('modal_afp_sign_payload')) {
    function modal_afp_sign_payload(array $payload, string $apiKey, string $merchantKey): string {
        unset($payload['afp_signature']);
        // Compared as strings: the JSON round-trip turns 5000.0 into 5000 and true into 1
        $payload = array_map(function ($v) {
            if (is_bool($v)) return $v ? '1' : '0';
            if ($v === null) return '';
            if (is_int($v) || is_float($v)) return (string)(0 + $v);
            return $v;
        }, $payload);
        ksort($payload);
        $key = hash('sha256', 'afp-signature|' . $apiKey . '|' . $merchantKey, true);
        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $key);
    }
}

if (!function_exists('modal_afp_current_url')) {
    function modal_afp_current_url(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $uri = strtok($uri, '?');

        return $protocol . '://' . $host . $uri;
    }
}

if (!function_exists('modal_afp_usualCurrency')) {
    function modal_afp_usualCurrency(?string $currency): string {
        if ($currency === null) {
            return '';
        }

        $symbols = [
            'XOF' => 'FCFA',
            'XAF' => 'FCFA',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'GNF' => 'GNF',
            'NGN' => '₦',
            'KES' => 'KSh',
            'GHS' => 'GH₵',
            'ZAR' => 'R',
        ];

        return $symbols[$currency] ?? $currency;
    }
}

if (!function_exists('modal_afp_esc')) {
    /**
     * @param string $context html, js, attr or url
     */
    function modal_afp_esc(?string $data, string $context = 'html'): string {
        if ($data === null || $data === '') {
            return '';
        }

        switch ($context) {
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            case 'js':
                return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            
            case 'attr':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            case 'url':
                return rawurlencode($data);
            
            default:
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
}

if (!function_exists('modal_afp_load_language')) {
    function modal_afp_load_language(string $lang = 'fr'): bool {
        global $MODAL_AFRIBAPAY_LANG_DATA;
        
        $langFile = dirname(__DIR__) . '/Language/' . $lang . '/Ck.php';
        
        if (!file_exists($langFile)) {
            $langFile = dirname(__DIR__) . '/Language/en/Ck.php';
            if (!file_exists($langFile)) {
                $MODAL_AFRIBAPAY_LANG_DATA = [];
                return false;
            }
        }
        
        $langData = require $langFile;
        
        if (!is_array($langData)) {
            $MODAL_AFRIBAPAY_LANG_DATA = [];
            return false;
        }
        
        $MODAL_AFRIBAPAY_LANG_DATA = $langData;
        return true;
    }
}

if (!function_exists('modal_afp_lang')) {
    /**
     * @param string $key "Ck.KEY" or "KEY"
     */
    function modal_afp_lang(string $key, array $params = []): string {
        global $MODAL_AFRIBAPAY_LANG_DATA;

        if (!isset($MODAL_AFRIBAPAY_LANG_DATA) || !is_array($MODAL_AFRIBAPAY_LANG_DATA)) {
            return $key;
        }

        $parts = explode('.', $key, 2);
        
        if (count($parts) === 2) {
            $file = $parts[0];
            $keyName = $parts[1];
            
            $translation = $MODAL_AFRIBAPAY_LANG_DATA[$keyName] ?? $key;
        } else {
            $translation = $MODAL_AFRIBAPAY_LANG_DATA[$key] ?? $key;
        }

        if (!empty($params)) {
            foreach ($params as $paramKey => $paramValue) {
                $translation = str_replace('{' . $paramKey . '}', $paramValue, $translation);
            }
        }

        return $translation;
    }
}

if (!function_exists('modal_afp_base_url')) {
    function modal_afp_base_url(string $path = ''): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        try {
            $params = TransactionConfig::getParameters();
            $checkoutPath = $params['checkoutPath'] ?? '/';
        } catch (Exception $e) {
            $checkoutPath = modal_afp_autoDetectCheckoutPath();
        }

        $baseUrl = $protocol . '://' . $host . rtrim($checkoutPath, '/');

        if (!empty($path)) {
            $path = ltrim($path, '/');
            return $baseUrl . '/' . $path . '';
        }

        return $baseUrl;
    }
}

if (!function_exists('modal_afp_normalize_path')) {
    function modal_afp_normalize_path(string $path): string {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}

if (!function_exists('modal_afp_url_path_for_dir')) {
    /**
     * URL path (e.g. "/afp-modal/checkout/") of a disk folder, relative to the running script
     */
    function modal_afp_url_path_for_dir(string $absDir): ?string {
        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptFile === '' || $scriptName === '') {
            return null;
        }

        $real = function (string $p): string {
            return modal_afp_normalize_path(realpath($p) ?: $p);
        };
        $fsFrom = explode('/', $real(dirname(modal_afp_normalize_path($scriptFile))));
        $fsTo   = explode('/', $real($absDir));

        // Case-insensitive on Windows (C:/ vs c:/)
        $isWindows = DIRECTORY_SEPARATOR === '\\' || preg_match('#^[A-Za-z]:/#', $fsTo[0] . '/');
        $i = 0;
        while (isset($fsFrom[$i], $fsTo[$i])
            && ($isWindows ? strcasecmp($fsFrom[$i], $fsTo[$i]) === 0 : $fsFrom[$i] === $fsTo[$i])) {
            $i++;
        }
        if ($i === 0) {
            return null;
        }

        $urlParts = array_values(array_filter(
            explode('/', str_replace('\\', '/', dirname(str_replace('\\', '/', $scriptName)))),
            'strlen'
        ));

        $levelsUp = count($fsFrom) - $i;
        if ($levelsUp > count($urlParts)) {
            return null;
        }

        $urlParts = array_merge(
            array_slice($urlParts, 0, count($urlParts) - $levelsUp),
            array_slice($fsTo, $i)
        );

        return $urlParts ? '/' . implode('/', $urlParts) . '/' : '/';
    }
}

if (!function_exists('modal_afp_autoDetectCheckoutPath')) {
    function modal_afp_autoDetectCheckoutPath(): string {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $checkoutDir = dirname(__DIR__);

        $path = modal_afp_url_path_for_dir($checkoutDir);
        if ($path !== null) {
            return $cached = $path;
        }

        // Fallback: DOCUMENT_ROOT
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $root = $documentRoot !== '' ? modal_afp_normalize_path(realpath($documentRoot) ?: $documentRoot) : '';
        $dir  = modal_afp_normalize_path(realpath($checkoutDir) ?: $checkoutDir);
        if ($root !== '') {
            $startsWith = DIRECTORY_SEPARATOR === '\\'
                ? stripos($dir . '/', $root . '/') === 0
                : strpos($dir . '/', $root . '/') === 0;
            if ($startsWith) {
                $rel = trim(substr($dir, strlen($root)), '/');
                return $cached = ($rel === '' ? '/' : '/' . $rel . '/');
            }
        }

        return $cached = '/checkout/';
    }
}

if (!function_exists('modal_afp_customer_config')) {
    function modal_afp_customer_config(): array {
        static $config = null;
        if ($config === null) {
            $path = dirname(__DIR__) . '/customer_config.inc.php';
            $config = is_file($path) ? (require $path) : [];
            $config = is_array($config) ? $config : [];
        }
        return $config;
    }
}

if (!function_exists('modal_afp_is_production')) {
    function modal_afp_is_production(): bool {
        return strtolower((string)(modal_afp_customer_config()['environment'] ?? 'sandbox')) === 'production';
    }
}

if (!function_exists('modal_afp_is_https')) {
    function modal_afp_is_https(): bool {
        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}

if (!function_exists('modal_afp_public_error')) {
    // Real error in sandbox, generic message in production (always logged)
    function modal_afp_public_error(Throwable $e, string $generic = 'The payment could not be processed. Please try again.'): string {
        error_log('[AfribaPay] ' . $e->getMessage());
        return modal_afp_is_production() && !($e instanceof AfribaPayPublicException) ? $generic : $e->getMessage();
    }
}

if (!function_exists('modal_afp_endpoint')) {
    /**
     * @param string $service 'api', 'cdn' or 'checkout' (overridable with apiUrl, cdnUrl, checkoutUrl)
     */
    function modal_afp_endpoint(string $service, ?string $environment = null): string {
        $config = modal_afp_customer_config();
        $production = strtolower($environment ?? ($config['environment'] ?? 'sandbox')) === 'production';
        $urls = $production
            ? ['api' => defined('API_URL') ? API_URL : 'https://api.afribapay.com',
               'cdn' => 'https://cdn.afribapay.com',
               'checkout' => 'https://checkout.afribapay.com']
            : ['api' => 'https://api-sandbox.afribapay.com',
               'cdn' => 'https://cdn-sandbox.afribapay.com',
               'checkout' => 'https://checkout-sandbox.afribapay.com'];
        $override = $config[$service . 'Url'] ?? null;
        $url = is_string($override) && $override !== '' ? $override : ($urls[$service] ?? null);
        if ($url === null) {
            throw new AfribaPayException("Unknown AfribaPay service: $service");
        }
        return rtrim($url, '/');
    }
}

if(!function_exists('modal_afp_cdn_url')) {
    function modal_afp_cdn_url(string $path = ''): string {
        return modal_afp_endpoint('cdn') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('modal_afp_resolve_base_url')) {
    function modal_afp_resolve_base_url(string $environment): string {
        return modal_afp_endpoint('api', $environment);
    }
}

