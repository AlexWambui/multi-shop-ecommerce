<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;
    protected $shortcode;
    protected $passkey;
    protected $callbackUrl;

    public function __construct()
    {
        $environment = config('mpesa.environment', 'sandbox');
        $this->baseUrl = $environment === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->shortcode = config('mpesa.shortcode', '174379');
        $this->passkey = config('mpesa.passkey');
        $this->callbackUrl = config('mpesa.callback_url');
    }

    /**
     * Generate OAuth Access Token
     */
    public function generateAccessToken(): ?string
    {
        $url = "{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials";

        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->timeout(30)
            ->get($url);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error('M-Pesa: Failed to generate access token', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return null;
    }

    /**
     * Generate STK Push Password
     */
    protected function generatePassword(string $timestamp): string
    {
        $data = $this->shortcode . $this->passkey . $timestamp;
        return base64_encode($data);
    }

    /**
     * Initiate STK Push
     */
    public function stkPush(
        string $phoneNumber,
        float $amount,
        string $accountReference,
        string $transactionDesc = 'Payment for order'
    ): array {
        // Format phone number
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $timestamp = now()->format('YmdHis');
        $password = $this->generatePassword($timestamp);
        $accessToken = $this->generateAccessToken();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa. Please try again.'
            ];
        }

        $payload = [
            'BusinessShortCode' => (int)$this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)round($amount),
            // 'Amount' => 1,
            'PartyA' => (int)$phoneNumber,
            'PartyB' => (int)$this->shortcode,
            'PhoneNumber' => (int)$phoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($transactionDesc, 0, 13),
        ];

        $url = "{$this->baseUrl}/mpesa/stkpush/v1/processrequest";

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
                return [
                    'success' => true,
                    'message' => $data['ResponseDescription'],
                    'data' => [
                        'merchant_request_id' => $data['MerchantRequestID'],
                        'checkout_request_id' => $data['CheckoutRequestID'],
                        'response_code' => $data['ResponseCode'],
                    ]
                ];
            }
        }

        // Handle error
        $error = $this->parseError($response->json());

        return [
            'success' => false,
            'message' => $error['message'],
            'error_code' => $error['code'] ?? null
        ];
    }

    /**
     * Query STK Push Status
     */
    public function queryStatus(string $checkoutRequestId): array
    {
        $timestamp = now()->format('YmdHis');
        $password = $this->generatePassword($timestamp);
        $accessToken = $this->generateAccessToken();

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to authenticate with M-Pesa'
            ];
        }

        $payload = [
            'BusinessShortCode' => (int)$this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $url = "{$this->baseUrl}/mpesa/stkpushquery/v1/query";

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'result_code' => $data['ResultCode'] ?? null,
                'result_desc' => $data['ResultDesc'] ?? null,
                'data' => $data
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to query transaction status'
        ];
    }

    /**
     * Format phone number to 254XXXXXXXX format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with 254
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }
        // If starts with 254, keep as is
        elseif (substr($phone, 0, 3) === '254') {
            // Already correct format
        }
        // If starts with +254, remove the +
        elseif (substr($phone, 0, 4) === '+254') {
            $phone = '254' . substr($phone, 4);
        }

        return $phone;
    }

    /**
     * Parse M-Pesa Error Response
     */
    protected function parseError(array $response): array
    {
        $errorCode = $response['errorCode'] ?? 'UNKNOWN';
        $errorMessage = $response['errorMessage'] ?? 'An unknown error occurred';

        $errorMap = [
            '400.002.02' => 'Invalid Business Shortcode. Please contact support.',
            '404.001.03' => 'Invalid Access Token. Please try again.',
            '500.001.1001' => 'Invalid credentials or merchant does not exist.',
            '500.003.02' => 'System is busy. Please try again in a few minutes.',
            '500.003.03' => 'Rate limit exceeded. Please wait before trying again.',
        ];

        return [
            'code' => $errorCode,
            'message' => $errorMap[$errorCode] ?? $errorMessage
        ];
    }
}
