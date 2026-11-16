<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bootstrap.php';

try {
    $rawData = file_get_contents('php://input');
    $request = json_decode($rawData, true);

    if ($request === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON received');
    }

    $afribapay = new AfribapayClass();
    $action = $request['action'] ?? null;

    if (!$action) {
        throw new Exception('Action is required');
    }

    switch ($action) {
        case 'checkout':
            $afribapay->getCheckout($request);
            break;

        case 'status':
            $paymentId = $request['payment_id'] ?? $request['order_id'] ?? null;
            if (!$paymentId) {
                throw new InvalidArgumentException('payment_id is required');
            }
            $afribapay->getStatus($paymentId);
            break;

        case 'walletOtp':
            $afribapay->sendWalletOtp($request);
            break;

        default:
            throw new Exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => modal_afp_public_error($e)
    ]);
}
