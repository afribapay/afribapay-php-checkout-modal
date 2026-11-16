<?php

require_once dirname(__DIR__) . '/checkout/modal/afribapay.modal.php';
require_once __DIR__ . '/common.php';

try {
    $sdk = new AfribaPayModal();
    
    $request = new AfribaPayModalRequest();
    
    $buttonHtml = $sdk->createCheckoutButton($request);
    
    echo getExampleHeader(
        'Example 10: Minimal Payment',
        'The simplest payment configuration (no parameters required).'
    );
    ?>
    
    <div class="alert alert-warning border-start border-warning border-4">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Important Notice</h5>
        <p>This example shows the simplest possible payment with no parameters required.</p>
        <p><strong>It is strongly recommended to include at least the following parameters:</strong></p>
        <ul>
            <li><code>amount</code> - Payment amount</li>
            <li><code>currency</code> - Currency (XOF, XAF, etc.)</li>
            <li><code>country</code> - Country code (BF, CI, etc.)</li>
            <li><code>order_id</code> - Unique order identifier</li>
        </ul>
    </div>
    
    <div class="mt-4">
        <h4 class="h5">Button with Minimal Configuration</h4>
        <div class="mt-3">
            <?php echo $buttonHtml; ?>
        </div>
    </div>
    
    <div class="mt-5">
        <h4 class="h5"><i class="bi bi-code-slash"></i> Code Used</h4>
        <div class="bg-light border-start border-primary border-4 p-3 rounded">
            <pre class="mb-0"><code><?php echo htmlspecialchars('
$modal = new AfribaPayModal();

$request = new AfribaPayRequest();

// All other parameters use default values
$buttonHtml = $modal->createCheckoutButton($request);
'); ?></code></pre>
        </div>
    </div>
    
    <div class="mt-4">
        <h4 class="h5">Recommended: Complete Configuration</h4>
        <div class="bg-light border-start border-primary border-4 p-3 rounded">
            <pre class="mb-0"><code><?php echo htmlspecialchars('
$request = new AfribaPayRequest();
$request->description = "Payment description";
$request->amount = 5000;
$request->currency = "XOF";
$request->country = "BF";
$request->order_id = "ORDER-" . time();
$request->reference_id = "REF-" . uniqid();
$request->company = "My Store";
'); ?></code></pre>
        </div>
    </div>
    
    <?php
    echo getExampleFooter();
    
} catch (AfribaPayException $e) {
    echo getExampleHeader('Error');
    echo '<div class="alert alert-danger">AfribaPay Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo getExampleFooter();
} catch (Exception $e) {
    echo getExampleHeader('Error');
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo getExampleFooter();
}
