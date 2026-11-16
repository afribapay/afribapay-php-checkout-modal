<?php

if (!defined('MODAL_CUSTOMER_CONFIG_PATH')) {
    define("MODAL_CUSTOMER_CONFIG_PATH", dirname(__FILE__, 2) . '/customer_config.inc.php');
}
class AfribaPayModalException extends \Exception {}
enum AfpModalEnvironment: string {
    case Sandbox = 'sandbox';
    case Production = 'production';
}
class AfpModalConfiguration {
    private string $environment;
    private array $urls;
    private $params;
    
    public function __construct() {
        $this->validateConfigInc();
        $this->environment = $this->getConfigInc('environment');
        if(!AfpModalEnvironment::tryFrom($this->environment)){
            throw new InvalidArgumentException("Invalid environment: $this->environment");
        }
        $checkoutPath = $this->getConfigInc('checkoutPath');
        $this->urls = [
            'production' => [
                'baseURL' => modal_afp_resolve_base_url('production'),
                'checkout' => $checkoutPath,
            ],
            'sandbox' => [
                'baseURL' => modal_afp_resolve_base_url('sandbox'),
                'checkout' => $checkoutPath,
            ]

        ];
    }

    private function validateConfigInc() {

        if (!file_exists(MODAL_CUSTOMER_CONFIG_PATH)) {
            throw new AfribaPayException("The customer_config.inc.php file was not found at: " . MODAL_CUSTOMER_CONFIG_PATH); 
        }

        $params = require MODAL_CUSTOMER_CONFIG_PATH;
        if (!is_array($params)) {
            throw new AfribaPayException("Invalid customer_config.inc.php file");
        }

        $this->params = $params;
        $requiredFields = [
            'apiUser', 
            'apiKey', 
            'agent_id', 
            'merchantKey', 
            'environment', 
            'lang', 
            'checkoutPath',
            'useCacheFolder',
            'cacheDirectory'
        ];
        foreach ($requiredFields as $field) {
            if (!isset($this->params[$field]) || ($field !== 'useCacheFolder' && empty($this->params[$field]))) {
                throw new AfribaPayException("Missing required field in config.inc.php file: $field");
            }
        }
        $useCacheValue = $this->params['useCacheFolder'];
        if (!is_bool($useCacheValue) && 
            $useCacheValue !== 'true' && 
            $useCacheValue !== 'false' && 
            $useCacheValue !== 1 && 
            $useCacheValue !== 0 &&
            $useCacheValue !== '1' && 
            $useCacheValue !== '0') {
            throw new AfribaPayException("Field 'useCacheFolder' in config.inc.php must be true or false");
        }
        if ($useCacheValue === 'true' || $useCacheValue === '1' || $useCacheValue === 1) {
            $this->params['useCacheFolder'] = true;
        } else if ($useCacheValue === 'false' || $useCacheValue === '0' || $useCacheValue === 0) {
            $this->params['useCacheFolder'] = false;
        }
        if (!in_array($params['lang'], ['en', 'fr'])) {
            throw new AfribaPayException("Field 'lang' in config.inc.php must be 'fr' or 'en' ");
        }

    }

    public function getConfigInc($key){
        return $this->params[$key] ?? null;
    }

    public function getCheckoutUrl(): string {
        return $this->urls[$this->environment]['checkout'] 
        ?? throw new AfribaPayException("Invalid environment");
    }

    public function getBaseUrl(): string {
        return $this->urls[$this->environment]['baseURL'] 
        ?? throw new AfribaPayException("Invalid environment");
    }

}
class AfribaPayModalRequest {
    private $requiredFields = [
        // TODO: add for seamless
        // 'description',
    ];
    private $allowedFields = [
        'amount', 
        'currency', 
        'order_id', 
        'reference_id', 
        'country', 
        'showCountries', 
        'notify_url',

        // TODO: add for seamless
        // 'description',
        // 'company',
        // 'checkout_name',
        // 'logo_url',
        // 'return_url',
        // 'cancel_url',
    ];
    private $data = [];

    public function __set($name, $value) {
        if (!in_array($name, $this->allowedFields)) {
            throw new AfribaPayException("Field '$name' is not allowed");
        }
        if ($name === 'showCountries') {
            if (!is_bool($value)) {
                throw new AfribaPayException("$name must be true or false");
            }
            $this->data[$name] = $value ? 1 : 0;
            return;
        }
        if (empty($value)) {
            throw new AfribaPayException("$name cannot be empty");
        }
        if($name == 'amount' && $value < 1){
            throw new AfribaPayException("$name cannot be less than 1");
        }
        if (in_array($name, self::URL_FIELDS, true) && !$this->isHttpUrl($value)) {
            throw new AfribaPayException("$name must be a valid http(s) URL");
        }
        $this->data[$name] = $this->sanitizeInput($value);
    }

    public function __get($name) {
        return $this->data[$name] ?? null;
    }

    private const URL_FIELDS = ['notify_url', 'return_url', 'cancel_url', 'logo_url'];

    private function isHttpUrl($url): bool {
        if (!is_string($url) || !filter_var(trim($url), FILTER_VALIDATE_URL)) {
            return false;
        }
        return in_array(strtolower((string)parse_url(trim($url), PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    // Stored raw (HTML escaping here broke URLs); escaped when displayed
    private function sanitizeInput($value) {
        return is_string($value) ? strip_tags(trim($value)) : $value;
    }
    
    public function validate() {
        foreach ($this->requiredFields as $field) {
            if (!isset($this->data[$field])) {
                throw new AfribaPayException("Missing required field: $field");
            }
        }
    }
    
    public function toArray() {
        return $this->data;
    }
}
class AfribaPayModal {
    private AfpModalConfiguration $config;
    private string $lang;
    private static bool $bootstrapLoaded = false;
    private static bool $formScriptLoaded = false;

    public function __construct() {
        $this->config = new AfpModalConfiguration();
        $this->lang =  $this->config->getConfigInc('lang');
        print $this->getBootstrapLink();
    }

    public function createCheckoutButton(
        AfribaPayModalRequest $request,
        string $buttonText = 'Pay',
        string $buttonColor = '#4CAF50',
        string $size = 'medium',
        array $additionnalClass = []
    ): string {
        $request->validate();
        $formId = 'afp-form-'.uniqid();
        $html = '';
        $html .= $this->createSubmitButton($formId, $buttonText, $buttonColor, $size, $additionnalClass);
        $html  .= (new AfpModal())->generateModal($formId, $this->prepareHiddenInputs($request->toArray()), $this->config->getCheckoutUrl(), $this->lang);
        return $html;
    }

    private function prepareHiddenInputs(array $data): array {
        $merchantKey = $this->config->getConfigInc('merchantKey');
        $payload = array_merge(
            $data,
            [
                'baseUrl' => $this->config->getBaseUrl(),
                'seamlessModalId' => 'seamless_modal_' . bin2hex(random_bytes(16)),
                'showCountries' => $data['showCountries'] ?? true,
            ]
        );
        $payload['afp_signature'] = modal_afp_sign_payload($payload, $this->config->getConfigInc('apiKey'), $merchantKey);
        return $payload;
    }

    private function getFormScript(): string {
        if (self::$formScriptLoaded) { return '';}
        self::$formScriptLoaded = true;
        $checkoutUrl = htmlspecialchars($this->config->getCheckoutUrl(), ENT_QUOTES, 'UTF-8');
        $script = '(function(){function a(){var b=document.querySelectorAll("form.afp-payment-form");b.forEach(function(c){if(c.hasAttribute("data-afp-initialized"))return;c.setAttribute("data-afp-initialized","true");var d=c.querySelector("button[type=\'submit\']");if(!d)return;d.addEventListener("click",function(e){e.preventDefault();var f=c.getAttribute("data-afp-data");if(!f){console.error("AfribaPay: Missing data");return}var g=c.querySelector("input[name=\'afp_data\']");if(g)g.remove();var h=document.createElement("input");h.type="hidden";h.name="afp_data";h.value=f;c.appendChild(h);c.action="' . $checkoutUrl . '";c.method="post";c.submit()})})}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",a)}else{a()}})();';
        return '<script>' . $script . '</script>';
    }

    private function createSubmitButton(
        string $formId,
        string $buttonText,
        string $buttonColor,
        string $size,
        array $additionnalClass
    ): string {
        $sizeClass = $this->getSizeClass($size);
        $colorClass = $this->getColorClass($buttonColor);
        $extraClasses = implode(' ', array_map('htmlspecialchars', $additionnalClass));
        $allClasses = trim("btn {$colorClass} {$sizeClass} afp-payment-btn {$extraClasses}");
        $inlineStyle = $this->isCustomColor($buttonColor) 
            ? sprintf('style="background-color: %s !important; border-color: %s !important; color: white;"', 
                htmlspecialchars($buttonColor, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($buttonColor, ENT_QUOTES, 'UTF-8')
              )
            : '';
        $safeModal= sprintf("data-bs-toggle=\"modal\" data-bs-target=\"#%s\"", htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'));
        return sprintf(
            '<button type="submit" class="%s" %s %s >%s</button>',
            $allClasses,
            $inlineStyle,
            $safeModal,
            htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8')
        );
    }

    private const BOOTSTRAP_CDN_URLS = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css',
        'https://unpkg.com/bootstrap@5.3.8/dist/css/bootstrap.min.css',
    ];

    private function getBootstrapLink(): string {
        if (self::$bootstrapLoaded) {
            return '';
        }
        self::$bootstrapLoaded = true;

        $option = $this->config->getConfigInc('bootstrapCss') ?? 'auto';
        if ($option === false || $option === 'false' || $option === '') {
            return '';
        }

        if ($option !== 'auto' && is_string($option)) {
            return sprintf(
                '<link id="afp-bootstrap-css" rel="stylesheet" href="%s">',
                htmlspecialchars($option, ENT_QUOTES, 'UTF-8')
            );
        }

        $urls      = self::BOOTSTRAP_CDN_URLS;
        $first     = array_shift($urls);
        $fallbacks = implode('|', $urls);

        // On load error, the browser switches to the next CDN
        $onError = "var f=(this.dataset.fallbacks||'').split('|').filter(Boolean);"
                 . "if(f.length){this.dataset.fallbacks=f.slice(1).join('|');this.href=f[0];}";

        return sprintf(
            '<link id="afp-bootstrap-css" rel="stylesheet" href="%s" data-fallbacks="%s" onerror="%s">',
            htmlspecialchars($first, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($fallbacks, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($onError, ENT_QUOTES, 'UTF-8')
        );
    }

    private function getSizeClass(string $size): string {
        return match (strtolower($size)) {
            'small', 'sm' => 'btn-sm',
            'large', 'lg' => 'btn-lg',
            'medium', 'md', '' => 'btn-md',
            default => 'btn-md',
        };
    }

    private function getColorClass(string $color): string {
        $colorMap = [
            'success' => 'btn-success',
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            'danger' => 'btn-danger',
            'warning' => 'btn-warning',
            'info' => 'btn-info',
            'dark' => 'btn-dark',
            'light' => 'btn-light',
        ];
        return $colorMap[$color] ?? $color;
    }

    private function isCustomColor(string $color): bool {
        return !in_array(strtolower($color), ['success', 'primary', 'secondary', 'danger', 'warning', 'info', 'dark', 'light']);
    }
}

class AfpModal {
    private function getButtonTexts(string $lang): array {
        $translations = [
            'fr' => [
                'back' => 'Retour',
                'loading' => 'Chargement en cours ...',
                'loadingError' => 'Erreur de chargement: '
            ],
            'en' => [
                'back' => 'Back',
                'loading' => 'Loading ... ',
                'loadingError' => 'Loading error : '
            ],
        ];
        return $translations[$lang] ?? $translations['fr'];
    }     

    public function generateModal(string $modalId, array $postData = [], $contentUrl = null, $lang = 'en'): string {
        if (empty($contentUrl)) { throw new AfribaPayException("Content URL is required"); }
        $protocol = $this->is_https() ? "https" : "http";
        $contentUrl = $protocol."://".$_SERVER['HTTP_HOST'].$contentUrl."checkout.php";
        $contentUrl = filter_var($contentUrl, FILTER_VALIDATE_URL);
        if ($contentUrl === false) { throw new AfribaPayException("Invalid content URL"); }
        $safeModalId = preg_replace('/[^a-zA-Z0-9_]/', '_', $modalId);
        $postDataJson = json_encode($postData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $buttonTexts = $this->getButtonTexts($lang);
        return "
            <div class=\"modal fade\" id=\"{$modalId}\" tabindex=\"-1\" aria-labelledby=\"{$modalId}Label\" aria-hidden=\"true\">
                <div class=\"modal-dialog modal-lg\">
                    <div class=\"modal-content\">
                        <div class=\"modal-header\">
                            <h5 class=\"modal-title\" id=\"{$modalId}Label\"></h5>
                            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>
                        </div>
                        <div class=\"modal-body\" id=\"{$modalId}_body\">
                            <div id=\"{$modalId}_content\"></div>
                        </div>
                        <div class=\"modal-footer\" style=\"display: none;\" id=\"{$modalId}_footer\">
                            <button type=\"button\" class=\"btn btn-primary\" id=\"{$modalId}_btnRetour\">{$buttonTexts['back']}</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>if(!document.querySelector('script[src*=\"bootstrap.bundle\"]')){var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js';document.head.appendChild(s);}</script>
            <script>if(typeof jQuery==='undefined'){var s=document.createElement('script');s.src='https://code.jquery.com/jquery-3.7.1.min.js';document.head.appendChild(s);}</script>
            <script>
                if (!window.{$safeModalId}_data) {
                    window.{$safeModalId}_data = {};
                }
                window.{$safeModalId}_data.postData = {$postDataJson};
                window.{$safeModalId}_data.language = '{$lang}';
                window.{$safeModalId}_data.translations = {
                    '{$lang}': {
                        'back': '{$buttonTexts['back']}',
                        'loading': '{$buttonTexts['loading']}'
                    }
                };
                document.addEventListener('DOMContentLoaded', function() {
                    var modal = document.getElementById('{$modalId}');
                    var contentDiv = document.getElementById('{$modalId}_content');
                    var footerDiv = document.getElementById('{$modalId}_footer');
                    var isLoaded = false;
                    if (typeof window.validateAfribaPayForm !== 'function') {
                        window.validateAfribaPayForm = function(form) { return false;};
                    }
                    modal.addEventListener('shown.bs.modal', function () {
                        if (document.activeElement) { document.activeElement.blur();}
                        if (!isLoaded) { loadContent();}
                    });
                    modal.addEventListener('hide.bs.modal', function () {
                        const focusedElement = modal.querySelector(':focus');
                        if (focusedElement) {focusedElement.blur();}
                    });
                    function loadContent() {
                        isLoaded = true;
                        footerDiv.style.display = 'none';
                        contentDiv.innerHTML = '<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"visually-hidden\">{$buttonTexts['loading']}</span></div><p class=\"mt-2\">{$buttonTexts['loading']}</p></div>';
                        fetch('{$contentUrl}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(window.{$safeModalId}_data.postData)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.status);
                            }
                            return response.text();
                        })
                        .then(html => {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;
                            processUrls(tempDiv);
                            while (contentDiv.firstChild) {contentDiv.removeChild(contentDiv.firstChild);}
                            Array.from(tempDiv.childNodes).forEach(node => {
                                if (node.nodeName !== 'SCRIPT') {
                                    contentDiv.appendChild(node.cloneNode(true));
                                }
                            });
                            handleScripts(tempDiv, '{$safeModalId}');
                            if (typeof $.fn.select2 === 'function') {
                                $(contentDiv).find('select.select2').select2();
                            }
                            isLoaded = true;
                            if (window.{$safeModalId}_data.formData) {
                                setTimeout(restoreFormData, 100);
                            }
                            const contentHtml = contentDiv.innerHTML;
                            handleTransactionStatus(contentHtml);
                            setupStatusObserver();
                        })
                        .catch(error => {
                            isLoaded = false;
                            contentDiv.innerHTML = '<div class=\"alert alert-danger\"> {$buttonTexts['loadingError']} ' + error.message + '</div>';
                            footerDiv.style.display = 'flex';
                        });
                    }
                    function handleTransactionStatus(contentHtml) {
                        if (contentHtml.includes('afp-success-status')) {
                            footerDiv.style.display = 'flex';
                            document.getElementById('{$modalId}_btnRetour').style.display = 'none';
                            window.{$safeModalId}_data.transactionStatus = 'success';
                        } else if (contentHtml.includes('afp-failed-status')) {
                            footerDiv.style.display = 'flex';
                            document.getElementById('{$modalId}_btnRetour').style.display = 'block';
                            window.{$safeModalId}_data.transactionStatus = 'failed';
                        } else if (contentHtml.includes('afp-retrying-status')) {
                            footerDiv.style.display = 'flex';
                            document.getElementById('{$modalId}_btnRetour').style.display = 'block';
                            window.{$safeModalId}_data.transactionStatus = 'retrying';
                        } else {
                            window.{$safeModalId}_data.transactionStatus = 'unknown';
                        }
                        const statusEvent = new CustomEvent('afp-status-change', { 
                            detail: { 
                                status: window.{$safeModalId}_data.transactionStatus,
                                modalId: '{$modalId}'
                            } 
                        });
                        document.dispatchEvent(statusEvent);
                    }
                    function setupStatusObserver() {
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                                    const currentContent = contentDiv.innerHTML;
                                    const previousStatus = window.{$safeModalId}_data.transactionStatus || 'unknown';
                                    if (window.{$safeModalId}_data.lastContent !== currentContent) {
                                        window.{$safeModalId}_data.lastContent = currentContent;
                                        handleTransactionStatus(currentContent);
                                    }
                                }
                            });
                        });
                        const config = { attributes: true, childList: true, subtree: true };
                        observer.observe(contentDiv, config);
                        let pollCount = 0;
                        let pollDelay = 2000;
                        let maxPolls = 30;

                        function schedulePoll() {
                            window.{$safeModalId}_data.statusTimeout = setTimeout(function() {
                                if (pollCount >= maxPolls) { return; }

                                const currentContent = contentDiv.innerHTML;
                                if (window.{$safeModalId}_data.lastPolledContent !== currentContent) {
                                    window.{$safeModalId}_data.lastPolledContent = currentContent;
                                    handleTransactionStatus(currentContent);
                                }

                                pollCount++;
                                pollDelay = Math.min(pollDelay * 1.1, 10000);
                                schedulePoll();
                            }, pollDelay);
                        }
                        schedulePoll();
                        modal.addEventListener('hidden.bs.modal', function cleanupModal() {
                            if (window.{$safeModalId}_data.statusTimeout) {
                                clearTimeout(window.{$safeModalId}_data.statusTimeout);
                            }
                            observer.disconnect();
                            modal.removeEventListener('hidden.bs.modal', cleanupModal);
                        });
                    }
                    function processUrls(container) {
                        var links = container.querySelectorAll('a[href]');
                        links.forEach(link => {
                            var href = link.getAttribute('href');
                            if (href && !href.startsWith('http') && !href.startsWith('//') && !href.startsWith('#')) {
                                link.href = new URL(href, '{$contentUrl}').href;
                            }
                        });
                        var srcs = container.querySelectorAll('[src]');
                        srcs.forEach(srcElem => {
                            var src = srcElem.getAttribute('src');
                            if (src && !src.startsWith('http') && !src.startsWith('//')) {
                                srcElem.src = new URL(src, '{$contentUrl}').href;
                            }
                        });
                    }
                    function handleScripts(container, safeId) {
                        var scripts = container.querySelectorAll('script');
                        scripts.forEach((script, index) => {
                            if (script.src) {
                                var scriptSrc = script.src;
                                var existingScripts = document.querySelectorAll('script[src=\"' + scriptSrc + '\"]');
                                if (existingScripts.length === 0) {
                                    var newScript = document.createElement('script');
                                    newScript.src = scriptSrc;
                                    newScript.defer = true;
                                    document.body.appendChild(newScript);
                                }
                            } else {
                                var scriptContent = script.textContent;
                                var wrappedScript = document.createElement('script');
                                wrappedScript.textContent = '(function(window, document, $) { ' +
                                    '// Create a unique context for each script execution\\n' +
                                    'var ' + safeId + '_context_' + index + ' = {};\\n' +
                                    scriptContent +
                                    '})(window, document, jQuery);';
                                document.body.appendChild(wrappedScript);
                            }
                        });
                    }
                    document.getElementById('{$modalId}_btnRetour').addEventListener('click', function() {
                        document.activeElement.blur();
                        saveFormData();
                        isLoaded = false;
                        loadContent();
                    });
                    function saveFormData() {
                        const forms = contentDiv.querySelectorAll('form');
                        if (!forms.length) return;
                        if (!window.{$safeModalId}_data.formData) {
                            window.{$safeModalId}_data.formData = {};
                        }
                        forms.forEach((form, formIndex) => {
                            const formData = {};
                            const inputs = form.querySelectorAll('input, select, textarea');
                            inputs.forEach(input => {
                                if (input.name && input.type !== 'button' && input.type !== 'submit' && input.type !== 'reset') {
                                    if (input.type === 'checkbox' || input.type === 'radio') {
                                        formData[input.name] = input.checked;
                                    }else {
                                        formData[input.name] = input.value;
                                    }
                                }
                            });
                            window.{$safeModalId}_data.formData['form_' + formIndex] = formData;
                        });
                    }
                    function restoreFormData() {
                        if (!window.{$safeModalId}_data.formData) return;
                        const forms = contentDiv.querySelectorAll('form');
                        if (!forms.length) return;
                        forms.forEach((form, formIndex) => {
                            const formData = window.{$safeModalId}_data.formData['form_' + formIndex];
                            if (!formData) return;
                            const inputs = form.querySelectorAll('input, select, textarea');
                            inputs.forEach(input => {
                                if (input.name && formData.hasOwnProperty(input.name)) {
                                    if (input.type === 'checkbox' || input.type === 'radio') {
                                        input.checked = formData[input.name];
                                    } else if (input.type !== 'file') {
                                        input.value = formData[input.name];
                                        if (input.tagName.toLowerCase() === 'select' && typeof $.fn.select2 === 'function') {
                                            $(input).trigger('change');
                                        }
                                    }
                                }
                            });
                        });
                    }
                });
            </script>
        ";
    }

    private function is_https() {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }
} 
