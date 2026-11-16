<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bootstrap.php';

try {
    $afribapay = new AfribapayClass();
    $params = $afribapay->formParameters;

    $country = $params['country'] ?? null;
    $currency = $params['currency'] ?? null;
    $showCountries = $params['showCountries'] ?? true;

    $countries = $afribapay->fetchCountries($country, $currency, $showCountries);

    echo json_encode($countries);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => modal_afp_public_error($e)
    ]);
}
