<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;

class PayPalAdaptiveService
{
    public function verifyEmail(string $email, bool $apiCreds = false): array
    {
        $creds = $this->credentials($apiCreds);
        $url = $this->adaptiveBaseUrl() . '/AdaptiveAccounts/GetVerifiedStatus';

        $response = Http::asForm()
            ->withHeaders($creds['headers'])
            ->post($url, [
                'requestEnvelope.errorLanguage' => 'en_US',
                'requestEnvelope.detailLevel' => 'ReturnAll',
                'emailAddress' => $email,
                'matchCriteria' => 'NONE',
            ]);

        if (!$response->ok()) {
            return ['success' => false, 'verified' => false, 'message' => 'Paypal service unavailable.'];
        }

        $data = $response->json();
        if (!$this->isAckSuccessful($data)) {
            $message = $this->extractErrorMessage($data, 'Paypal account not verified.');
            return ['success' => true, 'verified' => false, 'message' => $message];
        }

        return ['success' => true, 'verified' => true, 'message' => 'Verified'];
    }

    public function createAdaptivePayKey(
        float $adminAmount,
        float $merchantAmount,
        string $merchantEmail,
        string $returnUrl,
        string $cancelUrl,
        bool $apiCreds = true
    ): array {
        $creds = $this->credentials($apiCreds);
        $url = $this->adaptiveBaseUrl() . '/AdaptivePayments/Pay';

        $payload = [
            'actionType' => 'PAY_PRIMARY',
            'currencyCode' => 'CAD',
            'feesPayer' => 'EACHRECEIVER',
            'memo' => 'Example',
            'receiverList.receiver(0).amount' => $adminAmount,
            'receiverList.receiver(0).email' => $creds['admin_email'],
            'receiverList.receiver(0).primary' => 'true',
            'receiverList.receiver(1).amount' => $merchantAmount,
            'receiverList.receiver(1).email' => $merchantEmail,
            'receiverList.receiver(1).primary' => 'false',
            'requestEnvelope.errorLanguage' => 'en_US',
            'returnUrl' => $returnUrl,
            'cancelUrl' => $cancelUrl,
        ];

        $response = Http::asForm()
            ->withHeaders($creds['headers'])
            ->post($url, $payload);

        if (!$response->ok()) {
            return ['success' => false, 'pay_key' => null, 'message' => 'Paypal service unavailable.'];
        }

        $data = $response->json();
        if (!$this->isAckSuccessful($data)) {
            $message = $this->extractErrorMessage($data, 'Unable to generate pay key.');
            return ['success' => false, 'pay_key' => null, 'message' => $message];
        }

        return ['success' => true, 'pay_key' => $data['payKey'] ?? null, 'message' => 'Pay key generated'];
    }

    public function getPaymentDetails(string $payKey, bool $apiCreds = true): array
    {
        $creds = $this->credentials($apiCreds);
        $url = $this->adaptiveBaseUrl() . '/AdaptivePayments/PaymentDetails';

        $response = Http::asForm()
            ->withHeaders($creds['headers'])
            ->post($url, [
                'requestEnvelope.errorLanguage' => 'en_US',
                'payKey' => $payKey,
            ]);

        if (!$response->ok()) {
            return [
                'success' => false,
                'verified' => false,
                'payment_status' => null,
                'message' => 'Paypal service unavailable.',
                'data' => [],
            ];
        }

        $data = $response->json();
        if (!$this->isAckSuccessful($data)) {
            return [
                'success' => true,
                'verified' => false,
                'payment_status' => null,
                'message' => $this->extractErrorMessage($data, 'Unable to verify payment status.'),
                'data' => $data,
            ];
        }

        $paymentStatus = strtoupper((string)($data['status'] ?? $data['paymentExecStatus'] ?? ''));
        $verified = in_array($paymentStatus, ['COMPLETED', 'PROCESSED', 'PENDING'], true);

        return [
            'success' => true,
            'verified' => $verified,
            'payment_status' => $paymentStatus,
            'message' => $verified ? 'Payment has been received' : 'Payment is not completed yet',
            'data' => $data,
        ];
    }

    public function paymentUrlFromPayKey(string $payKey): string
    {
        return rtrim($this->checkoutBaseUrl(), '/') . '/cgi-bin/webscr?cmd=_ap-payment&paykey=' . urlencode($payKey);
    }

    private function adaptiveBaseUrl(): string
    {
        return config('paypal.mode') === 'live'
            ? 'https://svcs.paypal.com'
            : 'https://svcs.sandbox.paypal.com';
    }

    private function checkoutBaseUrl(): string
    {
        return config('paypal.mode') === 'live'
            ? 'https://www.paypal.com'
            : 'https://www.sandbox.paypal.com';
    }

    private function isAckSuccessful(array $data): bool
    {
        $ack = strtoupper((string)($data['responseEnvelope']['ack'] ?? ''));
        return in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true);
    }

    private function extractErrorMessage(array $data, string $fallback): string
    {
        return (string)($data['error'][0]['message'] ?? $fallback);
    }

    private function credentials(bool $apiCreds): array
    {
        if ($apiCreds) {
            return [
                'headers' => [
                    'X-PAYPAL-SECURITY-USERID' => API_USER_ID,
                    'X-PAYPAL-SECURITY-PASSWORD' => API_USER_PASS,
                    'X-PAYPAL-SECURITY-SIGNATURE' => API_USER_SIGN,
                    'X-PAYPAL-REQUEST-DATA-FORMAT' => 'NV',
                    'X-PAYPAL-RESPONSE-DATA-FORMAT' => 'JSON',
                    'X-PAYPAL-APPLICATION-ID' => API_APP_ID,
                    'X-PAYPAL-SANDBOX-EMAIL-ADDRESS' => API_SANDBOX_EMAIL,
                ],
                'admin_email' => API_ADMIN_EMAIL,
            ];
        }

        return [
            'headers' => [
                'X-PAYPAL-SECURITY-USERID' => USER_ID,
                'X-PAYPAL-SECURITY-PASSWORD' => USER_PASS,
                'X-PAYPAL-SECURITY-SIGNATURE' => USER_SIGN,
                'X-PAYPAL-REQUEST-DATA-FORMAT' => 'NV',
                'X-PAYPAL-RESPONSE-DATA-FORMAT' => 'JSON',
                'X-PAYPAL-APPLICATION-ID' => APP_ID,
                'X-PAYPAL-SANDBOX-EMAIL-ADDRESS' => SANDBOX_EMAIL,
            ],
                'admin_email' => ADMIN_EMAIL,
        ];
    }
}
