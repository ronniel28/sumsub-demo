<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SumsubService
{
    private $baseUrl;
    private $appToken;
    private $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.sumsub.base_url', env('SUMSUB_BASE_URL'));
        $this->appToken = env('SUMSUB_APP_TOKEN');
        $this->secretKey = env('SUMSUB_SECRET_KEY');
    }

    public function createApplicant($externalUserId)
    {
    // API Endpoint and Query Parameters
    $urlPath = "/resources/applicants";
    $levelName = "id-only";
    $queryString = "?levelName=" . $levelName;

    // Timestamp for X-App-Access-Ts header
    $timestamp = time();

    // HTTP Method
    $httpMethod = "POST";

    // Request Body
    $requestBody = json_encode([
        'externalUserId' => $externalUserId,
    ]);

    // Concatenate the string to sign
    $stringToSign = $timestamp . $httpMethod . $urlPath . $queryString . $requestBody;

    // Generate the signature
    $signature = hash_hmac('sha256', $stringToSign, $this->secretKey);

    // Headers
    $headers = [
        'X-App-Token' => $this->appToken,
        'X-App-Access-Ts' => $timestamp,
        'X-App-Access-Sig' => $signature,
        'Content-Type' => 'application/json',
    ];

    // Full URL with query string
    $url = $this->baseUrl . $urlPath . $queryString;

    try {
        // Make the API request
        $response = Http::withHeaders($headers)->post($url, [
            'externalUserId' => $externalUserId,
        ]);

        // Check for success
        if ($response->successful()) {
            return $response->json();
        }

        // Handle API errors
        return [
            'errorName' => $response->json()['errorName'] ?? 'Unknown error',
            'description' => $response->json()['description'] ?? 'No additional details available',
            'code' => $response->status(),
        ];
    } catch (\Exception $e) {
        // Handle exceptions

        return [
            'errorName' => 'Exception',
            'description' => $e->getMessage(),
            'code' => 500,
        ];
    }
    
    }

    public function generateWebSdk($applicantId)
    {
        $url = "{$this->baseUrl}/resources/applicants/$applicantId/websdk";
        $headers = [
            'Content-Type' => 'application/json',
            'X-App-Token' => $this->appToken,
        ];

        $response = Http::withHeaders($headers)->get($url);

        return $response->json();
    }
}