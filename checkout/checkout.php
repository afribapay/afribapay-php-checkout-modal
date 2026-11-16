<?php

require_once __DIR__ . '/bootstrap.php';

$afribapayClass = new AfribapayClass();
$params   = $afribapayClass->formParameters;
$checkoutId = uniqid('ck_');

$isModal = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
    (isset($params['AfpModalPost']) && !empty($params['AfpModalPost']))
);




?>

<?php if (!$isModal): ?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= modal_afp_esc($params['checkout_name'] ?? '') ?> | AfribaPay</title>
    <meta name="description" content="Un livre qui nourrit l'esprit… et aide ceux qui en ont besoin. Chaque achat soutient notre œuvre caritative">

    <meta name="theme-color" content="#ffffff">
    <script src="<?= modal_afp_cdn_url('assets/js/config.js') ?>" type="07653d78924daf8c9b880bbf-text/javascript"></script>
    <script src="<?= modal_afp_cdn_url('assets/vendors/simplebar/simplebar.min.js') ?>" type="07653d78924daf8c9b880bbf-text/javascript"></script>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="<?= modal_afp_cdn_url('assets/vendors/simplebar/simplebar.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="<?= modal_afp_cdn_url('assets/css/theme.min.css') ?>" rel="stylesheet" id="style-default">
    <link href="<?= modal_afp_cdn_url('assets/css/user.css') ?>" rel="stylesheet" id="user-style-default">
</head>
<body class="bg-light">

<main class="main" id="top">
        <div class="container-fluid p-0" data-layout="container-fluid">
                                                    <div class="content">
<?php else: ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<?php endif; ?>

<header class="<?= $isModal ? 'py-2' : 'bg-white border-bottom py-3' ?>">
    <div class="container">
        <div class="d-flex align-items-center <?= $isModal ? 'justify-content-center' : 'justify-content-between' ?>">

            <?php if (!$isModal): ?>
            <div class="d-flex align-items-center gap-3">

            <?php
                $logo = (!empty($params['weblogo']) && filter_var($params['weblogo'], FILTER_VALIDATE_URL))
                    ? modal_afp_esc($params['weblogo'])
                    : modal_afp_cdn_url('assets/img/logos/afribapay.png');
            ?>

            <img src="<?= $logo ?>"
                 alt="Logo"
                 class="img-fluid"
                 style="max-height: clamp(34px, 6vw, 56px); object-fit: contain;">

                <?php
                    $currentLang = 'en';
                    $query       = $_GET ?? [];

                    $buildUrl = function ($lang) use ($query) {
                        $query['lang'] = $lang;
                        return modal_afp_current_url() . '?' . http_build_query($query);
                    };
                ?>

            </div>
            <?php endif; ?>

            <div class="d-flex flex-row align-items-center gap-2 gap-md-3 my-<?= $isModal ? '1' : '3' ?> fs-10">
                <div id="progressStep1" class="d-flex align-items-center px-3 py-2 rounded-pill bg-primary text-white">
                    <span class="badge rounded-pill bg-white text-primary fw-bold me-2">1</span>
                    <span class="<?= $isModal ? '' : 'd-none d-md-inline' ?>"><?= modal_afp_lang('Ck.PAYMENT_INFORMATION') ?></span>
                    <span class="badge bg-light d-none ms-2" id="badgeStep1">•</span>
                </div>

                <div id="progressStep2" class="d-flex align-items-center px-3 py-2 rounded-pill bg-light text-muted">
                    <span class="badge rounded-pill bg-white text-muted fw-bold me-2">2</span>
                    <span class="<?= $isModal ? '' : 'd-none d-md-inline' ?>"><?= modal_afp_lang('Ck.CONFIRMATION') ?></span>
                    <span class="badge bg-light d-none ms-2" id="badgeStep2">•</span>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="py-3">
    <div class="container">
        <div class="row g-4">
                   
            <div class="<?= $isModal ? 'col-12' : 'col-lg-7' ?>">
               <div class="card border-0 shadow-sm rounded-4">
                  <div class="card-body p-3 <?= $isModal ? '' : 'p-md-5' ?>">
              
                        <h1 class="fw-bold fs-6 mb-3 text-truncate">
                            <?= modal_afp_esc(html_entity_decode($params['checkout_name'] ?? ''), 'html') ?>
                            <div class="mt-2 small"><small class="text-muted fw-normal"><i class="bi bi-credit-card text-primary me-1"></i> <?= modal_afp_lang('Ck.PAYMENT_INFORMATION') ?></small></div>
                        </h1>

                        <form id="afribapayPaymentForm"
                              method="POST"
                              onsubmit="return validateAfribaPayForm()"
                              novalidate>

                            <div id="statusMessage"
                                 class="alert alert-success alert-dismissible fade show d-none mb-4"
                                 role="alert">
                                <button class="btn-close" data-bs-dismiss="alert"></button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                                    <?= modal_afp_lang('Ck.COUNTRY') ?>
                                </label>
                                <select id="afribapayCountry"
                                        name="country"
                                        class="form-select rounded-3"
                                        required>
                                    <option value=""><?= modal_afp_lang('Ck.COUNTRY_FIRST_CHOICE') ?></option>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="afribapayCurrencyBlock">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-cash-coin text-danger me-2"></i>
                                    <?= modal_afp_lang('Ck.CURRENCY') ?>
                                </label>
                                <select id="afribapayCurrency"
                                        name="currency"
                                        class="form-select rounded-3"
                                        required>
                                    <option value=""><?= modal_afp_lang('Ck.CURRENCY_FIRST_CHOICE') ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-hdd-network-fill text-danger me-2"></i>
                                    <?= modal_afp_lang('Ck.OPERATOR') ?>
                                </label>
                                <select id="afribapayOperator"
                                        name="operator"
                                        class="form-select rounded-3"
                                        required>
                                    <option value=""><?= modal_afp_lang('Ck.OPERATOR_FIRST_CHOICE') ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-telephone-fill text-danger me-2"></i>
                                    <?= modal_afp_lang('Ck.PHONE') ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text" id="countryCode">
                                        <i class="bi bi-flag"></i>
                                    </span>
                                    <input type="tel"
                                           id="afribapayPhone"
                                           name="phone"
                                           class="form-control"
                                           placeholder="600000000"
                                           required>
                                </div>
                            </div>

                            <?php if (empty($params['amount'])): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-currency-exchange text-danger me-2"></i>
                                        <?= modal_afp_lang('Ck.AMOUNT') ?>
                                    </label>
                            
                                    <div class="input-group">
                                        <input type="number"
                                               id="afribapayAmount"
                                               name="amount"
                                               class="form-control"
                                               min="1"
                                               placeholder="<?= modal_afp_lang('Ck.AMOUNT') ?>"
                                               required>
                            
                                        <span class="input-group-text fw-bold" id="currencyTextAddon">—</span>
                                    </div>
                                </div>
                            <?php else: ?>
                            
                                <input type="hidden"
                                       id="afribapayAmount"
                                       name="amount"
                                       value="<?= modal_afp_esc((string)$params['amount'], 'attr') ?>">
                            <?php endif; ?>

                            <div class="mb-3" id="otpRow" style="display: none;">
                                <div class="p-3 bg-light border border-danger border-opacity-25 rounded-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-shield-lock-fill text-danger me-2"></i>
                                        <?= modal_afp_lang('Ck.OTP_CODE') ?>
                                    </label>
                                    <input type="text"
                                           id="afribapayOtp_code"
                                           name="otp_code"
                                           class="form-control"
                                           placeholder="<?= modal_afp_lang('Ck.OTP_CODE') ?>">
                                    <div id="afribapayOtpMessage" class="alert alert-danger mt-2"></div>
                                </div>
                            </div>

                            <?php foreach (['notify_url','return_url','cancel_url','order_id','reference_id'] as $hidden): ?>
                                <?php if (!empty($params[$hidden])): ?>
                                    <input type="hidden" id="<?= $hidden ?>" name="<?= $hidden ?>"
                                           value="<?= rawurlencode($params[$hidden]) ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <div class="mt-4 mb-3">
                                <button id="afribapaySubmitPayment"
                                        type="submit"
                                        class="btn btn-success w-100 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2"
                                        data-button-text="<?= modal_afp_lang('Ck.PAY_BUTTON') ?>"
                                        data-initial-amount="<?= modal_afp_esc((string)($params['amount'] ?? ''), 'attr') ?>"
                                        data-initial-currency="<?= modal_afp_esc((string)($params['currency'] ?? ''), 'attr') ?>">
                            
                                    <span class="fs-6 d-flex align-items-center">
                                        <i class="bi bi-cash-coin me-2"></i>
                                    </span>
                            
                                    <span class="d-none d-sm-inline" id="buttonTextDesktop" style="font-size:1.05rem;">
                                        <?= modal_afp_esc(modal_afp_lang('Ck.PAY_BUTTON')) ?>
                            
                                        <?php if (!empty($params['amount'])): ?>
                                            <span class="px-2 py-1 bg-white bg-opacity-25 rounded-pill ms-1 fw-bold">
                                                <?= number_format($params['amount'], 0, ',', '.') ?>
                                                <?= modal_afp_esc(modal_afp_usualCurrency($params['currency'] ?? null)) ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                            
                                    <span class="d-inline d-sm-none" id="buttonTextMobile" style="font-size:1.05rem;">
                                        <?= modal_afp_esc(modal_afp_lang('Ck.PAY_BUTTON')) ?>
                            
                                        <?php if (!empty($params['amount'])): ?>
                                            <span class="fw-bold">
                                                <?= number_format($params['amount'], 0, ',', '.') ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>                           
                                </button>
                            </div>

                            <?php 
                                $termsUrl = ($lang ?? 'fr') === 'en'
                                    ? 'https://www.afribapay.com/en/terms-and-conditions/'
                                    : 'https://www.afribapay.com/fr/legal-gc/';
                            ?>                           
                            <p class="text-center small text-muted mt-3">
                                <?= modal_afp_lang('Ck.TERMS_TEXT') ?>
                                <strong><?= modal_afp_lang('Ck.PAY_BUTTON') ?></strong>
                                <?= modal_afp_lang('Ck.TERMS_ACCEPT') ?>
                            
                                <a target="_blank" href="<?= modal_afp_esc($termsUrl) ?>" class="fw-bold">
                                    <?= modal_afp_lang('Ck.TERMS_LINK') ?>
                                </a>
                            </p>
                        </form>

                        <hr class="my-4">

                        <div class="p-3 bg-light border rounded-3">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-lock-fill text-primary me-2"></i>
                                <?= modal_afp_lang('Ck.SIMPLE_SECURE_PAYMENT') ?>
                            </h6>

                            <p class="small text-muted mb-2">
                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                <strong><?= modal_afp_lang('Ck.SECURE_PAYMENT_TITLE') ?></strong>
                                – <?= modal_afp_lang('Ck.SECURE_PAYMENT_DESC') ?>
                            </p>

                            <p class="small text-muted mb-0">
                                <i class="bi bi-headset text-primary me-1"></i>
                                <strong><?= modal_afp_lang('Ck.SUPPORT_TITLE') ?></strong>
                                – <?= modal_afp_lang('Ck.SUPPORT_DESC') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$isModal): ?>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top:20px;">
                    <div class="card-body p-3 p-md-5">

                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-receipt-cutoff text-primary me-2"></i>
                            <?= modal_afp_lang('Ck.ORDER_SUMMARY') ?>
                        </h5>

                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;">
                                <i class="bi bi-shop"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">
                                    <?= modal_afp_esc($params['company'] ?? 'AfribaPAY') ?>
                                </h6>
                                <small class="text-muted">
                                    <?= modal_afp_esc($params['checkout_name'] ?? '') ?>
                                </small>
                            </div>
                        </div>

                        <?php if (!empty($params['description'])): ?>                       
                            <?php
                                $logoUrl = (!empty($params['weblogo']) && filter_var($params['weblogo'], FILTER_VALIDATE_URL))
                                    ? modal_afp_esc($params['weblogo'])
                                    : null;
                            ?>                       
                            <div class="p-2 mb-3">                      
                                <div class="d-flex align-items-start gap-2 flex-wrap flex-md-nowrap">                       
                                    <?php if ($logoUrl): ?>
                                        <div class="flex-shrink-0 text-center text-md-start mb-2 mb-md-0" style="max-width:120px;">
                                            <img 
                                                src="<?= $logoUrl ?>" 
                                                alt="Merchant Logo"
                                                class="img-fluid"
                                                style="max-height:120px; width:auto;"
                                            >
                                        </div>
                                    <?php endif; ?>
                        
                                    <div class="flex-grow-1 text-muted fs-10">
                                        <?= nl2br(modal_afp_esc(html_entity_decode($params['description'] ?? ''), 'html')) ?>
                                    </div>                       
                                </div>                      
                            </div>                       
                        <?php endif; ?>

                        <?php if (!empty($params['amount'])): ?>
                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5"><?= modal_afp_lang('Ck.TOTAL') ?></span>
                                    <span class="fw-bold text-primary fs-6">
                                        <?= number_format($params['amount'], 0, ',', '.') ?>
                                        <?= modal_afp_esc(modal_afp_usualCurrency($params['currency'] ?? null)) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
"use strict";
let ussdText = null;
let waveWindowOpened = false;

(function (global) {
    const AfribapayClass = {
        afribapayFormParam: <?= json_encode($params ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,

        getCheckout: async function (data) {
            alertMessage("<?= modal_afp_esc(modal_afp_lang('Ck.PENDING_MESSAGE')) ?>", "info");
            replaceFormWithLoadingSpinner();

            try {
                const response = await fetch("<?= modal_afp_base_url('ck/process.php') ?>", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: "checkout", ...data })
                });
                const result = await response.json();
                waitResponse(result);
            } catch (error) {
                replaceFormWithFailledIcon("<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_MESSAGE')) ?>");
                switchBadge("danger");
            }
        },

        retryTransaction: async function (paymentId) {
            alertMessage("<?= modal_afp_esc(modal_afp_lang('Ck.RETRY_CHECK_MESSAGE')) ?>", "info");
            replaceFormWithLoadingSpinner();
            checkTransactionStatus(paymentId);
        }
    };

    async function waitResponse(response) {

        response = response.response ?? response;

        const submitButton = document.getElementById("afribapaySubmitPayment");

        if (response.error) {
            const errorMessage = response.message || response.error.message || "<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_MESSAGE')) ?>";
            replaceFormWithFailledIcon(errorMessage);
            switchBadge("danger");
            return;
        }

        if (response.data) {
            const data = response.data;

            if (data.status === "PENDING") {
                let message =
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><?= modal_afp_esc(modal_afp_lang("Ck.PENDING_MESSAGE")) ?>';

                if (data.provider_link && !waveWindowOpened && isValidUrl(data.provider_link)) {
                    openWaveFront(data.provider_link);
                    waveWindowOpened = true;
                }

                if (data.ussd_code) {
                    message = `<div class="d-flex align-items-start">
                            <i class="bi bi-phone me-3 fs-4"></i>
                            <div>
                                <strong><?= modal_afp_esc(modal_afp_lang('Ck.PAYMENT_IN_PROGRESS')) ?></strong><br>
                                <small><?= modal_afp_esc(modal_afp_lang('Ck.DIAL_CODE_INSTRUCTION')) ?> <code class="bg-white px-2 py-1 rounded">${data.ussd_code}</code> <?= modal_afp_esc(modal_afp_lang('Ck.DIAL_CODE_SUFFIX')) ?>.</small>
                            </div>
                        </div>`;
                }

                alertMessage(message, "info");
                // Several payments can share the merchant order_id: follow this one by its transaction_id
                checkTransactionStatus(data.transaction_id || data.order_id);

            } else if (data.status === "SUCCESS") {
                replaceFormWithSuccessIcon();
                addTransactionInfoCompact(data);
                switchBadge("success");

            } else {
                const failedMessage = data.message || data.error_message || "<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_MESSAGE')) ?>";
                replaceFormWithFailledIcon(failedMessage);
                addTransactionInfoCompact(data);
                switchBadge("danger");
            }

        } else {
            replaceFormWithFailledIcon("<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_OPERATOR')) ?>");
            switchBadge("danger");
        }
    }

    function isValidUrl(url) {
        const pattern = /^(https?:\/\/)([a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+)(:\d+)?(\/[^\s]*)?$/;
        return pattern.test(url);
    }

    function openWaveFront(url) {
        window.location.href = url;
    }

    function isOnlyNumber(value) {
        if (!value || value.trim().length === 0) return false;
        return /^\d+$/.test(value.trim());
    }

    async function sendWalletOtp(data) {
        return new Promise((resolve, reject) => {
            fetch("<?= modal_afp_base_url('ck/process.php') ?>", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "walletOtp", data: data })
            }).then(response => {
                if (!response.ok) {
                    reject(new Error("<?= modal_afp_esc(modal_afp_lang('Error.WALLET_OTP_GENERATION_ERROR')) ?>"));
                }
                return response.json();
            }).then(result => {
                if (result.httpCode !== 200 && result.response && result.response.error) {
                    reject(new Error(result.response.error.message || "<?= modal_afp_esc(modal_afp_lang('Error.WALLET_OTP_GENERATION_FAILED')) ?>"));
                 } else {
                    resolve(result);
                }
            }).catch(error => {
                reject(error);
            });
        });
    }

    function redirectToOrigin() {
        if (document.referrer) {
            window.location.href = document.referrer;
        } else {
            window.location.href = "/";
        }
    }

    function sdkFirstAuthenticationFailledIcon(message = null) {
        alertMessage();
        const errorMessage = message ?? "<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_MESSAGE')) ?>";
        document.getElementById("afribapayPaymentForm").innerHTML = `
            <div class="text-center p-4">
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${errorMessage}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <div class="my-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" fill="currentColor" class="text-danger" viewBox="0 0 16 16">
                        <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg>
                </div>

                <div class="mt-3 text-center">
                    <button onclick="redirectToOrigin()" class="btn btn-secondary px-4 py-3 rounded-3 fw-semibold">
                        <i class="bi bi-arrow-left me-2"></i><?= modal_afp_esc(modal_afp_lang('Ck.RETURN_BUTTON')) ?>
                    </button>
                </div>
            </div>
        `;
    }

    function switchBadge(status) {
        const step1  = document.getElementById("progressStep1");
        const step2  = document.getElementById("progressStep2");
        const badge1 = document.getElementById("badgeStep1");
        const badge2 = document.getElementById("badgeStep2");
    
        if (!step1 || !step2) return;
    
        step1.className = "d-flex align-items-center px-3 py-2 rounded-pill bg-light text-muted w-100 w-md-auto";
        step2.className = "d-flex align-items-center px-3 py-2 rounded-pill bg-light text-muted w-100 w-md-auto";
    
        badge1.className = "badge bg-light d-none ms-auto ms-md-2";
        badge1.innerHTML = `<i class="bi bi-dot"></i>`;
    
        badge2.className = "badge bg-light d-none ms-auto ms-md-2";
        badge2.innerHTML = `<i class="bi bi-dot"></i>`;
    
        let color = null;
    
        if (status === "success")   color = "success";
        if (status === "danger")    color = "danger";
        if (status === "warning")   color = "warning";
    
        if (color) {
            step2.classList.remove("bg-light", "text-muted");
            step2.classList.add(`bg-${color}`, "text-white");
    
            badge2.classList.remove("bg-light", "d-none");
            badge2.classList.add("bg-white", `text-${color}`);
    
            if (color === "success") {
                badge2.innerHTML = `<i class="bi bi-check-circle-fill"></i>`;
            }
            else if (color === "danger") {
                badge2.innerHTML = `<i class="bi bi-x-circle-fill"></i>`;
            }
            else if (color === "warning") {
                badge2.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i>`;
            }
        }
    }
    
    function addTransactionInfoCompact(data) {
        const container = document.getElementById("transactionInfoContainer");
        if (!container) return;

        const formatDateTime = (dateString) => {
            if (!dateString) return { text: "N/A" };
            const date = new Date(dateString);

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            const hours = String(date.getHours()).padStart(2, "0");
            const minutes = String(date.getMinutes()).padStart(2, "0");
            const seconds = String(date.getSeconds()).padStart(2, "0");

            const timeText = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

            return { text: timeText };
        };

        const formattedDate = formatDateTime(data.date_created);

        container.innerHTML = `
            <div class="bg-light rounded p-3 border">
                <div class="text-center mb-3">
                    <div class="fw-bold fs-5 text-dark">${data.amount_total || "N/A"} ${data.currency || ""}</div>
                    <small class="text-muted"><i class="bi bi-cash-stack text-success"></i> <?= modal_afp_esc(modal_afp_lang('Ck.TOTAL')) ?></small>
                </div>
                
                <div class="d-flex flex-column gap-2">
                    <div class="border-bottom pb-2">
                        <div class="small text-muted mb-1">
                            <i class="bi bi-fingerprint me-1"></i>
                            <?= modal_afp_esc(modal_afp_lang('Ck.TRANSACTION_ID')) ?>
                        </div>
                        <div class="fw-bold small" title="${data.transaction_id || "N/A"}">${data.transaction_id || "N/A"}</div>
                    </div>
                    
                    <div class="border-bottom pb-2">
                        <div class="small text-muted mb-1">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= modal_afp_esc(modal_afp_lang('Ck.DATE_LABEL')) ?>
                        </div>
                        <div class="fw-bold small">${data.date_created || formattedDate.text}</div>
                    </div>
                    
                    <div class="pb-0">
                        <div class="small text-muted mb-1">
                            <i class="bi bi-phone me-1"></i>
                            <?= modal_afp_esc(modal_afp_lang('Ck.OPERATOR')) ?>
                        </div>
                        <div class="fw-bold small">${data.operator || "N/A"} +${data.phone_number || "N/A"}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function replaceFormWithSuccessIcon() {
        alertMessage();
        document.getElementById("afribapayPaymentForm").innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center min-vh-50 p-3">
                <div class="card border-0 mx-auto" style="width: 100%; max-width: 480px;">
                    <div class="card-body p-3">
                        <div class="text-center mb-3">
                            <h2 class="text-success fw-bold mb-1">
                                <i class="bi bi-check-circle me-2"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.SUCCESS_MESSAGE')) ?>
                            </h2>
                            <p class="text-muted fw-bold small mb-0"><?= modal_afp_esc(modal_afp_lang('Ck.TRANSACTION_SUCCESS_DESC')) ?></p>
                        </div>
                        
                        <div id="transactionInfoContainer" class="transaction-info-compact"></div>
                        
                        <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
                            <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm px-3 py-2">
                                <i class="bi bi-arrow-left me-1"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.RETURN_BUTTON')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function replaceFormWithFailledIcon(message = null) {
        alertMessage();
        const errorMessage = message ?? "<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_MESSAGE')) ?>";
        document.getElementById("afribapayPaymentForm").innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center min-vh-50 p-3">
                <div class="card border-0 mx-auto" style="width: 100%; max-width: 480px;">
                    <div class="card-body p-3">
                        <div class="text-center my-3">
                            <h4 class="text-danger fw-bold mb-1">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.TRANSACTION_FAILED')) ?>
                            </h4>
                            <div class="alert alert-danger my-3">${errorMessage}</div>
                        </div>
                        
                        <div id="transactionInfoContainer" class="transaction-info-compact"></div>
                        
                        <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
                            <button onclick="location.reload()" class="btn btn-danger btn-sm px-3 py-2">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.RETRY_BUTTON')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function replaceFormWithLoadingSpinner() {
        document.getElementById("afribapayPaymentForm").innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center min-vh-50 p-1">                
                <div class="card border-0 mx-auto" style="width: 100%; max-width: 480px;">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <h3 class="text-primary fw-bold mb-2">
                                <i class="bi bi-hourglass-split me-2"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.PROCESSING_TITLE')) ?>
                            </h3>
                            <p class="text-muted mb-3"><?= modal_afp_esc(modal_afp_lang('Ck.PROCESSING_DESC')) ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <div class="mb-2">
                                <div class="spinner-border mb-3" style="width: 40px; height: 40px;color:#d31f38" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            
                            ${
                                ussdText
                                    ? `
                                <div class="alert alert-warning">
                                    <i class="bi bi-telephone me-2"></i>
                                    <?= modal_afp_esc(modal_afp_lang('Ck.DIAL_CODE_INSTRUCTION')) ?> 
                                    <strong class="text-primary">${ussdText}</strong>
                                    <?= modal_afp_esc(modal_afp_lang('Ck.DIAL_CODE_SUFFIX')) ?>
                                </div>
                            `
                                    : ""
                            }
                        </div>
                        
                        <div class="mt-3">
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated w-100" role="progressbar"></div>
                            </div>
                            <p class="text-muted small mb-0"><?= modal_afp_esc(modal_afp_lang('Ck.VERIFICATION_IN_PROGRESS')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function replaceFormWithRetryButton(paymentId) {
        alertMessage();
        document.getElementById("afribapayPaymentForm").innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center min-vh-50 p-3">
                <div class="card border-0 mx-auto" style="width: 100%; max-width: 480px;">
                    <div class="card-body p-3">
                        <div class="text-center mb-3">
                            <h4 class="text-warning fw-bold mb-1">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.VERIFICATION_REQUIRED')) ?>
                            </h4>
                            <p class="text-muted small mb-0"><?= modal_afp_esc(modal_afp_lang('Ck.CKECK_BUTTON_MESSAGE')) ?></p>
                        </div>
                        
                        <div id="transactionInfoContainer" class="transaction-info-compact"></div>
                        
                        <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
                            <button type="button" onclick="AfribapayClass.retryTransaction('${paymentId}')" class="btn btn-warning btn-sm px-3 py-2">
                                <i class="bi bi-arrow-repeat me-1"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.CKECK_BUTTON')) ?>
                            </button>
                            <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm px-3 py-2">
                                <i class="bi bi-arrow-left me-1"></i>
                                <?= modal_afp_esc(modal_afp_lang('Ck.RETURN_BUTTON')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async function checkTransactionStatus(paymentId) {
        let attempts = 0;
        const maxAttempts = 4;
        let stopErrorFallback = false;
    
        const interval = setInterval(async () => {
            try {
                attempts++;
                const response = await fetch("<?= modal_afp_base_url('ck/process.php') ?>", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: "status", payment_id: paymentId })
                });
    
                if (!response.ok) throw new Error("<?= modal_afp_esc(modal_afp_lang('Error.STATUS_VERIFICATION_ERROR')) ?>");
    
                const result = await response.json();
                const normalized = result.response ?? result;
    
                if (normalized.error) {
                    if (!stopErrorFallback) {
                        clearInterval(interval);
                        replaceFormWithFailledIcon(normalized.error.message);
                        switchBadge("danger");
                    }
                    return;
                }
    
                if (normalized.data) {
                    const data = normalized.data;
    
                    if (data.status !== "PENDING") {
                        clearInterval(interval);
                        waitResponse(result);
                        return;
                    }
    
                    alertMessage(`
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <strong><?= modal_afp_esc(modal_afp_lang('Ck.VERIFICATION_IN_PROGRESS')) ?></strong><br>
                        <small><?= modal_afp_esc(modal_afp_lang('Ck.VERIFICATION_ATTEMPT')) ?> ${attempts} <?= modal_afp_esc(modal_afp_lang('Ck.VERIFICATION_OF')) ?> ${maxAttempts}.</small>
                    `, "info");
    
                    if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        stopErrorFallback = true;
    
                        showPendingManualRetry(paymentId, data);
                        switchBadge("warning");
                    }
    
                    return;
                }
    
                if (!stopErrorFallback) {
                    clearInterval(interval);
                    replaceFormWithFailledIcon("<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_OPERATOR')) ?>");
                    switchBadge("danger");
                }
    
            } catch (error) {
    
                if (!stopErrorFallback) {
                    clearInterval(interval);
                    replaceFormWithFailledIcon("<?= modal_afp_esc(modal_afp_lang('Ck.CONNECTION_ERROR')) ?>");
                    switchBadge("danger");
                }
            }
        }, 15000);
    }
    
    function showPendingManualRetry(paymentId, data) {
        alertMessage(`
            <strong><?= modal_afp_esc(modal_afp_lang('Ck.PAYMENT_STILL_PROCESSING')) ?></strong><br>
            <small><?= modal_afp_esc(modal_afp_lang('Ck.PAYMENT_STILL_PROCESSING_DESC')) ?></small>
        `, "warning");
    
        replaceFormWithRetryButton(paymentId);
        addTransactionInfoCompact(data);
    }

    async function initPaymentForm() {
        ussdText = null;
        $("#afribapayCurrencyBlock").hide();

        try {
            const response = await fetch("<?= modal_afp_base_url('ck/countries.php') ?>");
            const CountriesData = await response.json();

            if (CountriesData.error) {
                alertMessage(CountriesData.message, "danger");
                return;
            }

            const countrySelect  = $("#afribapayCountry");
            const currencySelect = $("#afribapayCurrency");
            const operatorSelect = $("#afribapayOperator");
            const otpBlock       = $("#otpRow");

            const countryPlaceholder  = countrySelect.find('option[value=""]').first().clone();
            const currencyPlaceholder = currencySelect.find('option[value=""]').first().clone();
            const operatorPlaceholder = operatorSelect.find('option[value=""]').first().clone();

            countrySelect.empty().append(countryPlaceholder.clone());

            for (const [key, value] of Object.entries(CountriesData)) {
                countrySelect.append(
                    `<option value="${key}">${value.country_flag} ${value.country_name}</option>`
                );
            }

            countrySelect.on("change", function () {
                $("#statusMessage").hide();
                const countryCode = this.value;

                currencySelect.empty().append(currencyPlaceholder.clone());
                operatorSelect.empty().append(operatorPlaceholder.clone());

                otpBlock.hide();
                $("#afribapayOtp_code").removeAttr("required").val("");
                $("#afribapayOtpMessage").hide();
                $("#afribapayCurrencyBlock").removeClass("d-none");

                if (countryCode && CountriesData[countryCode]) {
                    updatePhoneField(CountriesData[countryCode].prefix, CountriesData[countryCode].country_flag);
                    const currencies   = CountriesData[countryCode].currencies;
                    const currencyKeys = Object.keys(currencies);

                    if (currencyKeys.length === 1) {
                        const currency = currencyKeys[0];
                        $("#afribapayCurrencyBlock").addClass("d-none");
                        currencySelect.append(`<option value="${currency}">${currencies[currency].currency}</option>`);
                        currencySelect.val(currency).trigger("change");
                        updatePaymentButtonText(currency, CountriesData);
                        updateCurrencyAddon(countryCode, currency, CountriesData);
                     } else {
                        for (const c in currencies) {
                            currencySelect.append(
                                `<option value="${c}">${currencies[c].currency}</option>`
                            );
                        }
                        updatePaymentButtonText("", CountriesData);
                    }
                }
                validateForm();
            });

            currencySelect.on("change", function () {
                const countryCode  = countrySelect.val();
                const currencyCode = this.value;

                operatorSelect.empty().append(operatorPlaceholder.clone());
                otpBlock.hide();
                $("#afribapayOtp_code").removeAttr("required").val("");
                $("#afribapayOtpMessage").hide();

                if (countryCode && currencyCode && CountriesData[countryCode].currencies[currencyCode]) {
                    CountriesData[countryCode].currencies[currencyCode].operators.forEach((operator) => {
                        operatorSelect.append(
                            `<option value="${operator.operator_code}">${operator.operator_name}</option>`
                        );
                    });
                }

                updatePaymentButtonText(currencyCode, CountriesData);
                updateCurrencyAddon(countryCode, currencyCode, CountriesData);
                validateForm();
            });

            operatorSelect.on("change", function () {
                const countryCode  = countrySelect.val();
                const currencyCode = currencySelect.val();
                const operatorCode = this.value;

                otpBlock.hide();
                $("#afribapayOtp_code").removeAttr("required").val("");
                $("#afribapayOtpMessage").hide();

                if (countryCode && currencyCode && CountriesData[countryCode].currencies[currencyCode]) {
                    const operator = CountriesData[countryCode].currencies[currencyCode].operators.find(
                        (op) => op.operator_code === operatorCode
                    );

                    if (operator) {
                        if (operator.otp_required === 1 && operator.wallet !== 1) {
                            otpBlock.show();
                            $("#afribapayOtpMessage")
                                .show()
                                .html(`<?= modal_afp_esc(modal_afp_lang('Ck.DIAL_CODE_INSTRUCTION')) ?> <b>${operator.ussd_code}</b> <?= modal_afp_esc(modal_afp_lang('Ck.DIAL_CODE_SUFFIX')) ?>`)
                                .removeClass("alert-info")
                                .addClass("alert-danger");
                            document.getElementById("afribapayOtp_code").setAttribute("required", "");
                        }

                        if (operator.otp_required === 0 && operator.ussd_code !== "") {
                            ussdText = operator.ussd_code;
                        }
                    }
                }

                validateForm();

                const selectedOperator =
                    CountriesData[countryCode]?.currencies[currencyCode]?.operators.find(
                        (op) => op.operator_code === operatorCode
                    );

                if (selectedOperator && selectedOperator.wallet === 1 && selectedOperator.otp_required === 1) {
                    if ($("#walletButton").length === 0) {
                        const walletButton = $("<button>", {
                            id: "walletButton",
                            class: "btn btn-primary w-100",
                            type: "button",
                            html: `<i class="bi bi-shield-lock me-2"></i><?= modal_afp_esc(modal_afp_lang('Ck.WALLET_OTP_BUTTON')) ?>`,
                            click: function (event) {
                                event.preventDefault();
                                if (validateAfribaPayForm()) {
                                    const otpData = {
                                        operator: operatorCode.toLowerCase(),
                                        country: $("#afribapayCountry").val(),
                                        phone: $("#afribapayPhone").val(),
                                        amount: $("#afribapayAmount").val(),
                                        currency: $("#afribapayCurrency").val()
                                    };

                                    sendWalletOtp(otpData)
                                        .then((response) => {
                                            otpBlock.show();
                                            $("#afribapayOtpMessage")
                                                .show()
                                                .html(`<?= modal_afp_esc(modal_afp_lang('Ck.WALLET_OTP_MESSAGE')) ?>`)
                                                .removeClass("alert-danger")
                                                .addClass("alert-info");
                                            document.getElementById("afribapayOtp_code").setAttribute("required", "");
                                            $("#walletButton").addClass("d-none");
                                            $("#afribapaySubmitPayment").removeClass("d-none");
                                        })
                                        .catch((error) => {
                                            alertMessage(
                                                error.message || "<?= modal_afp_esc(modal_afp_lang('Error.GENERAL_ERROR_RETRY')) ?>",
                                                "danger"
                                            );
                                        });
                                }
                            }
                        });

                        $("#afribapaySubmitPayment").after(walletButton);
                        $("#afribapaySubmitPayment").addClass("d-none");
                    }
                 } else {
                    $("#walletButton").remove();
                    $("#afribapaySubmitPayment").removeClass("d-none");
                }
            });

            // Only preselect real values, to keep the placeholder
            if (AfribapayClass.afribapayFormParam.country &&
                AfribapayClass.afribapayFormParam.country.trim() !== "") {
            
                countrySelect.val(AfribapayClass.afribapayFormParam.country).trigger("change");
            }
            
            if (AfribapayClass.afribapayFormParam.currency &&
                AfribapayClass.afribapayFormParam.currency.trim() !== "") {
            
                currencySelect.val(AfribapayClass.afribapayFormParam.currency).trigger("change");
            }

        } catch (error) {
            alertMessage("<?= modal_afp_esc(modal_afp_lang('Ck.FAILLED_OPERATOR')) ?>", "danger");
        }
    }

    function updatePhoneField(prefix, countryFlag) {
        const safeFlag = countryFlag ? countryFlag : "";
        $("#countryCode").html(`
            <span class="me-2">${safeFlag}</span> +${prefix}
        `);
    }

    function validateForm() {
        const countrySelected  = $("#afribapayCountry").val() !== "";
        const currencySelected = $("#afribapayCurrency").val() !== "";
        const operatorSelected = $("#afribapayOperator").val() !== "";
        $("#afribapaySubmitPayment").prop(
            "disabled",
            !(countrySelected && currencySelected && operatorSelected)
        );
    }

    function alertMessage(message, type) {
        const statusMessage = $("#statusMessage");
        if (!message) {
            statusMessage.hide();
            return;
        }

        const alertClass =
            {
                danger: "alert-danger",
                warning: "alert-warning",
                success: "alert-success",
                info: "alert-info"
            }[type] || "alert-info";

        statusMessage
            .removeClass("alert-success alert-warning alert-danger alert-info")
            .addClass(`alert ${alertClass}`)
            .html(message)
            .show();
    }

    function AfribaPayCheckout() {
        alertMessage();
        const formData = {
            country: $("#afribapayCountry").val(),
            currency: $("#afribapayCurrency").val(),
            operator: $("#afribapayOperator").val(),
            phone: $("#afribapayPhone").val(),
            amount: $("#afribapayAmount").val(),
            otp_code: $("#afribapayOtp_code").val(),
            notify_url: $("#notify_url").val(),
            return_url: $("#return_url").val(),
            cancel_url: $("#cancel_url").val(),
            order_id: $("#order_id").val(),
            reference_id: $("#reference_id").val()
        };

        if (validateAfribaPayForm()) {
            AfribapayClass.getCheckout(formData);
        }
    }

    function updatePaymentButtonText(currencyCode, CountriesData) {
        const button            = document.getElementById("afribapaySubmitPayment");
        const buttonTextDesktop = document.getElementById("buttonTextDesktop");
        const buttonTextMobile  = document.getElementById("buttonTextMobile");
    
        if (!button) return;
    
        const initialAmount   = button.getAttribute("data-initial-amount");
        const initialCurrency = button.getAttribute("data-initial-currency");
        const buttonText      = button.getAttribute("data-button-text");
    
        if (!initialAmount || initialAmount === "") {
            if (buttonTextDesktop) buttonTextDesktop.textContent = buttonText;
            if (buttonTextMobile)  buttonTextMobile.textContent  = buttonText;
            return;
        }
    
        const formattedAmount = new Intl.NumberFormat("fr-FR", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(parseFloat(initialAmount));
    
        let newButtonText = buttonText + " " + formattedAmount;
    
        const currencyToUse = currencyCode || initialCurrency || "";
        let currencyLabel   = "";
    
        if (currencyToUse && CountriesData) {
            const country = document.getElementById("afribapayCountry")?.value;
    
            if (
                country &&
                CountriesData[country] &&
                CountriesData[country].currencies &&
                CountriesData[country].currencies[currencyToUse]
            ) {
                currencyLabel = CountriesData[country].currencies[currencyToUse].currency;
            }
        }
    
        if (!currencyLabel && initialCurrency && currencyToUse === initialCurrency) {
            currencyLabel = initialCurrency;
        }
    
        if (currencyLabel) {
            newButtonText += " " + currencyLabel;
        }
    
        if (buttonTextDesktop) buttonTextDesktop.textContent = newButtonText;
        if (buttonTextMobile)  buttonTextMobile.textContent  = newButtonText;
    }

    function updateCurrencyAddon(countryCode, currencyCode, CountriesData) {
        const addon = document.getElementById("currencyTextAddon");
        if (!addon) return;
    
        addon.textContent = "—";
    
        if (!countryCode || !currencyCode) {
            return;
        }
    
        let label = "";
    
        if (currencyCode === "XOF" || currencyCode === "XAF") {
            label = "Fcfa";
        }
    
        if (
            CountriesData[countryCode] &&
            CountriesData[countryCode].currencies &&
            CountriesData[countryCode].currencies[currencyCode]
        ) {
            const cLabel = CountriesData[countryCode].currencies[currencyCode].currency;
            if (!label) label = cLabel;
        }
    
        addon.textContent = label || currencyCode;
    }

    function validateAfribaPayForm() {
        const form    = document.getElementById("afribapayPaymentForm");
        let isValid   = true;

        const validateField = (element, condition) => {
            element.classList.toggle("is-invalid", !condition);
            return condition;
        };

        form.querySelectorAll("select[required], input[required]").forEach((field) => {
            isValid = validateField(field, field.value.trim() !== "") && isValid;
        });

        const phoneField = document.getElementById("afribapayPhone");
        const phoneValid = phoneField.value.trim().length >= 5 && isOnlyNumber(phoneField.value);
        isValid = validateField(phoneField, phoneValid) && isValid;

        const otpField    = document.getElementById("afribapayOtp_code");
        const otpValue    = otpField.value.trim();
        const otpRequired = otpField.hasAttribute("required");

        if (otpValue.length >= 1 || otpRequired) {
            const otpValid = isOnlyNumber(otpValue) && (!otpRequired || otpValue.length >= 4);
            isValid = validateField(otpField, otpValid) && isValid;
         } else {
            otpField.classList.remove("is-invalid");
        }

        return isValid;
    }

    $(document).ready(function () {
        const mbsError = <?= json_encode($mbsError ?? false) ?>;

        if (mbsError !== false) {
            sdkFirstAuthenticationFailledIcon(mbsError);
        } else {
            initPaymentForm();
        }

        $("#afribapayPaymentForm").on("submit", function (e) {
            e.preventDefault();
            AfribaPayCheckout();
        });
    });

    global.AfribapayClass                    = AfribapayClass;
    global.initPaymentForm                   = initPaymentForm;
    global.validateAfribaPayForm             = validateAfribaPayForm;
    global.redirectToOrigin                  = redirectToOrigin;
    global.sdkFirstAuthenticationFailledIcon = sdkFirstAuthenticationFailledIcon;

})(window);
</script>

<script>
(function injectCheckoutIdHeader() {
    try {
        window.AfribaPayCheckoutId = '<?= htmlspecialchars($checkoutId ?? "", ENT_QUOTES, "UTF-8") ?>';
        const originalFetch = window.fetch;
        window.fetch = function (input, init) {
            init        = init || {};
            init.headers = init.headers || {};
            const headers = new Headers(init.headers);
            if (window.AfribaPayCheckoutId) {
                headers.set("X-Checkout-Id", window.AfribaPayCheckoutId);
            }
            init.headers = headers;
            return originalFetch(input, init);
        };
    } catch (e) {
    }
})();
</script>

<?php if (!$isModal): ?>
<footer class="footer">
  <div class="container">
    <div class="row g-0 justify-content-between fs-10 mt-4 mb-3">
      <div class="col-12 col-sm-auto text-center">
        <p class="mb-0 text-600">&copy; 2023 - 2026 <strong><span>AfribaPAY</span></strong>. All Rights Reserved.</p>
      </div>
      <div class="col-12 col-sm-auto text-center">
        <p class="mb-0 text-600">v0.2</p>
      </div>
    </div>
   </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</body>
</html>
<?php endif; ?>