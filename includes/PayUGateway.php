<?php
/**
 * KABUTO ESPORTS - PayU Payment Gateway Integration
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class PayUGateway
{
    /**
     * Generate PayU hash for payment initiation.
     * Formula: sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||salt)
     */
    public static function generateHash(array $params): string
    {
        $hashStr = implode('|', [
            PAYU_KEY,
            $params['txnid'],
            $params['amount'],
            $params['productinfo'],
            $params['firstname'],
            $params['email'],
            $params['udf1'] ?? '',
            $params['udf2'] ?? '',
            $params['udf3'] ?? '',
            $params['udf4'] ?? '',
            $params['udf5'] ?? '',
            '',
            '',
            '',
            '',
            '',
            PAYU_SALT,
        ]);

        return hash('sha512', $hashStr);
    }

    /**
     * Verify PayU response hash (reverse hash).
     * Formula: sha512(salt|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
     */
    public static function verifyHash(array $responseParams): bool
    {
        $params = $responseParams;

        $hashStr = implode('|', [
            PAYU_SALT,
            $params['status'] ?? '',
            '',
            '',
            '',
            '',
            '',
            $params['udf5'] ?? '',
            $params['udf4'] ?? '',
            $params['udf3'] ?? '',
            $params['udf2'] ?? '',
            $params['udf1'] ?? '',
            $params['email'] ?? '',
            $params['firstname'] ?? '',
            $params['productinfo'] ?? '',
            $params['amount'] ?? '',
            $params['txnid'] ?? '',
            PAYU_KEY,
        ]);

        $calculatedHash = hash('sha512', $hashStr);
        return hash_equals($calculatedHash, strtolower($params['hash'] ?? ''));
    }

    /**
     * Build and return a self-submitting HTML form to redirect to PayU.
     */
    public static function buildPaymentForm(
        string $txnId,
        float  $amount,
        string $productInfo,
        string $firstName,
        string $email,
        string $phone,
        string $udf1 = '',   // Registration ID
        string $udf2 = '',   // Tournament ID
        string $udf3 = ''    // Extra data
    ): string {
        $amountFormatted = number_format((float)$amount, 2, '.', '');

        $params = [
            'key'         => PAYU_KEY,
            'txnid'       => $txnId,
            'amount'      => $amountFormatted,
            'productinfo' => $productInfo,
            'firstname'   => $firstName,
            'email'       => $email,
            'phone'       => $phone,
            'surl'        => PAYU_SUCCESS_URL,
            'furl'        => PAYU_FAILURE_URL,
            'udf1'        => $udf1,
            'udf2'        => $udf2,
            'udf3'        => $udf3,
            'udf4'        => '',
            'udf5'        => '',
        ];

        $params['hash'] = self::generateHash($params);

        $html  = '<!DOCTYPE html><html><head><title>Redirecting to PayU...</title>';
        $html .= '<style>body{background:#0a0a0f;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;flex-direction:column;gap:16px;}';
        $html .= '.loader{width:48px;height:48px;border:4px solid #2a2a3e;border-top-color:#f5a623;border-radius:50%;animation:spin 0.8s linear infinite;}';
        $html .= '@keyframes spin{to{transform:rotate(360deg);}}</style></head>';
        $html .= '<body><div class="loader"></div><p>Redirecting to secure payment gateway...</p>';
        $html .= '<form id="payuForm" method="POST" action="' . PAYU_BASE_URL . '">';

        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES) . '" value="' . htmlspecialchars((string)$value, ENT_QUOTES) . '">';
        }

        $html .= '</form>';
        $html .= '<script>document.addEventListener("DOMContentLoaded",function(){document.getElementById("payuForm").submit();});</script>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Verify payment via PayU Verify API.
     */
    public static function verifyPayment(string $txnId): ?array
    {
        $commandStr = 'verify_payment';
        $hashStr    = hash('sha512', PAYU_KEY . '|' . $commandStr . '|' . $txnId . '|' . PAYU_SALT);

        $postData = http_build_query([
            'key'     => PAYU_KEY,
            'command' => $commandStr,
            'var1'    => $txnId,
            'hash'    => $hashStr,
        ]);

        $ch = curl_init(PAYU_VERIFY_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('PayU verify cURL error: ' . $error);
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Record payment attempt in DB.
     */
    public static function recordPayment(
        int    $registrationId,
        string $txnId,
        float  $amount,
        string $status = 'initiated',
        array  $gatewayResponse = []
    ): int {
        Database::query(
            "INSERT INTO payments (registration_id, transaction_id, amount, status, gateway_response, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $registrationId,
                $txnId,
                $amount,
                $status,
                json_encode($gatewayResponse),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Update payment record on callback.
     */
    public static function updatePayment(
        string $txnId,
        string $status,
        array  $response = [],
        ?string $payuTxnId   = null,
        ?string $bankRefNum  = null,
        ?string $paymentMode = null
    ): void {
        Database::query(
            "UPDATE payments SET 
             status = ?, gateway_response = ?, payu_txn_id = ?, bank_ref_num = ?, 
             payment_mode = ?, hash_verified = 1, updated_at = NOW()
             WHERE transaction_id = ?",
            [$status, json_encode($response), $payuTxnId, $bankRefNum, $paymentMode, $txnId]
        );
    }
}
