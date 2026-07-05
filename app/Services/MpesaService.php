<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
     * Generate OAuth Access Token with Caching
     */
    public function generateAccessToken(): ?string
    {
        // Check if we have a cached token
        $cacheKey = 'mpesa_access_token_' . $this->shortcode;
        
        if (Cache::has($cacheKey)) {
            Log::info('M-Pesa: Using cached access token');
            return Cache::get($cacheKey);
        }

        Log::info('M-Pesa: Generating new access token');
        
        $url = "{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials";

        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->timeout(30)
            ->get($url);

        if ($response->successful()) {
            $token = $response->json()['access_token'];
            
            // Cache for 50 minutes (tokens expire in 1 hour)
            Cache::put($cacheKey, $token, 3000);
            
            Log::info('M-Pesa: Access token generated and cached successfully');
            return $token;
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
            'PartyA' => (int)$phoneNumber,
            'PartyB' => (int)$this->shortcode,
            'PhoneNumber' => (int)$phoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($transactionDesc, 0, 13),
        ];

        Log::info('M-Pesa STKPush Request', [
            'phone' => $phoneNumber,
            'amount' => $amount,
            'account_reference' => $accountReference,
            'callback_url' => $this->callbackUrl
        ]);

        $url = "{$this->baseUrl}/mpesa/stkpush/v1/processrequest";

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($url, $payload);

        $responseData = $response->json();

        Log::info('M-Pesa STKPush Response', [
            'status' => $response->status(),
            'response' => $responseData
        ]);

        if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
            return [
                'success' => true,
                'message' => $responseData['ResponseDescription'],
                'data' => [
                    'merchant_request_id' => $responseData['MerchantRequestID'],
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'response_code' => $responseData['ResponseCode'],
                ]
            ];
        }

        // Handle error
        $error = $this->parseError($responseData);

        return [
            'success' => false,
            'message' => $error['message'],
            'error_code' => $error['code'] ?? null,
            'raw_response' => $responseData
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
            Log::error('M-Pesa Query: Failed to get access token');
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

        Log::info('M-Pesa Query Request', [
            'checkout_request_id' => $checkoutRequestId,
            'shortcode' => $this->shortcode
        ]);

        $url = "{$this->baseUrl}/mpesa/stkpushquery/v1/query";

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($url, $payload);

        $responseData = $response->json();

        Log::info('M-Pesa Query Response', [
            'status' => $response->status(),
            'response' => $responseData
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'result_code' => $responseData['ResultCode'] ?? null,
                'result_desc' => $responseData['ResultDesc'] ?? null,
                'data' => $responseData
            ];
        }

        Log::error('M-Pesa Query Failed', [
            'status' => $response->status(),
            'response' => $responseData
        ]);

        return [
            'success' => false,
            'message' => 'Failed to query transaction status',
            'status_code' => $response->status()
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
        // Check for different error response structures
        $errorCode = $response['errorCode'] ?? $response['ResponseCode'] ?? 'UNKNOWN';
        $errorMessage = $response['errorMessage'] ?? $response['ResponseDescription'] ?? 'An unknown error occurred';

        // Common M-Pesa error codes
        $errorMap = [
            '400.002.02' => 'Invalid Business Shortcode. Please contact support.',
            '404.001.03' => 'Invalid Access Token. Please try again.',
            '500.001.1001' => 'Invalid credentials or merchant does not exist.',
            '500.003.02' => 'System is busy. Please try again in a few minutes.',
            '500.003.03' => 'Rate limit exceeded. Please wait before trying again.',
            '1' => 'Insufficient balance or invalid amount.',
            '1037' => 'Transaction cancelled by user.',
            '1032' => 'Transaction cancelled by user.',
            '1001' => 'Invalid phone number format.',
            '2001' => 'Insufficient balance.',
            '2002' => 'Transaction limit exceeded.',
        ];

        return [
            'code' => $errorCode,
            'message' => $errorMap[(string)$errorCode] ?? $errorMessage
        ];
    }

    /**
     * Clear cached token (useful for testing)
     */
    public function clearTokenCache(): void
    {
        $cacheKey = 'mpesa_access_token_' . $this->shortcode;
        Cache::forget($cacheKey);
        Log::info('M-Pesa: Token cache cleared');
    }
}