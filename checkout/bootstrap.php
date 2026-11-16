<?php

if (defined('AFRIBAPAY_MODAL_LOADED')) {
    return;
}
define('AFRIBAPAY_MODAL_LOADED', true);

require_once __DIR__ . '/Helpers/Helpers.php';
require_once __DIR__ . '/modal/config.inc.php';
require_once __DIR__ . '/modal/afribapay.class.php';

global $MODAL_AFRIBAPAY_LANG_DATA;
$MODAL_AFRIBAPAY_LANG_DATA = [];

try{

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $_POST = json_decode($rawInput, true) ?? [];
            }
        } else {
            $afp_data = $_POST['afp_data'] ?? null;
            if(!is_null($afp_data) && is_string($afp_data)){
                $_POST = json_decode(base64_decode($afp_data), true);
            }
        }
    }
    TransactionConfig::initialize();
    
} catch(AfribaPaySignatureException $e){
    http_response_code(400);
    echo modal_afp_esc($e->getMessage());
    exit;
} catch(Exception $e){
    echo modal_afp_esc(modal_afp_public_error($e));
}
