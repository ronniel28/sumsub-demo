<?php

namespace App\Http\Controllers;

use App\Services\SumsubService;
use GuzzleHttp\Client;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class SumsubController extends Controller
{
    protected $sumsubService;
    private $baseUrl;
    private $appToken;
    private $secretKey;

    public function __construct(SumsubService $sumsubService)
    {
        $this->sumsubService = $sumsubService;
        $this->baseUrl = config('services.sumsub.base_url', env('SUMSUB_BASE_URL'));
        $this->appToken = env('SUMSUB_APP_TOKEN');
        $this->secretKey = env('SUMSUB_SECRET_KEY');
    }

    // Create Applicant
    public function createApplicant(Request $request)
    {
        $externalUserId = 'user_' . time(); // Generate a unique user ID

        $response = $this->sumsubService->createApplicant($externalUserId);

        if (isset($response['error'])) {
            return response()->json($response, 400);
        }

        return response()->json([
            'applicantId' => $response['id'],
            'externalUserId' => $externalUserId
        ]);
    }

    // Generate WebSDK Token
    public function generateToken(Request $request)
    {
        $user = auth()->user();

        $appToken = $this->appToken; // Replace with your App Token
        $secretKey = $this->secretKey; // Replace with your Secret Key
        $ttlInSecs = 600; // Time-to-live for the token in seconds
        $userId = $request->externalUserId; 
        $levelName  = $request->levelName;
        
        // Generate timestamp and create signature
        $timestamp = time();
        $method = 'POST';
        $urlPath = '/resources/accessTokens/sdk';
        $body = json_encode([
            'levelName' => $levelName,
            'userId' => $userId,
            'ttlInSecs' => $ttlInSecs,
            'applicantInfo' => [
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
            ],
        ]);
        
        $signature = hash_hmac('sha256', $timestamp . $method . $urlPath . $body, $secretKey);
        
        // Create HTTP client
        $client = new Client();
        
        // Make API request
        try {
            $response = $client->request('POST', 'https://api.sumsub.com' . $urlPath, [
                'headers' => [
                    'X-App-Token' => $appToken,
                    'X-App-Access-Ts' => $timestamp,
                    'X-App-Access-Sig' => $signature,
                    'Content-Type' => 'application/json',
                ],
                'body' => $body,
            ]);
            $responseBody = $response->getBody()->getContents();

            // Decode the JSON response
            $data = json_decode($responseBody, true);
        
            // Extract token and userId
            $token = $data['token'] ?? null;
            $extractedUserId = $data['userId'] ?? null;

                
            // Return as JSON response
            return response()->json([
                'status' => 'success',
                'userId' => $extractedUserId,
                'token' => $token,
            ], 200);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Handle error response
            $errorResponse = $e->getResponse()->getBody()->getContents();
            return response()->json([
                'status' => 'error',
                'message' => 'Client error',
                'details' => $errorResponse,
            ], 400);
    
        } catch (\Exception $e) {

            // Handle other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateWebSdkLink(Request $request)
    {
        // Replace with your actual app token and secret key
        $appToken =  env('SUMSUB_APP_TOKEN');
        $secretKey = env('SUMSUB_SECRET_KEY');
        $timestamp = time();

        $getToken = $this->generateToken($request);
        $responseData = $getToken->getOriginalContent();
        if ($responseData['status'] === 'success') {
            $token = $responseData['token'];
        }
        // Extract levelName and externalUserId from the request
        $levelName = $request->input('levelName', 'id-only'); // Default level name
        $externalUserId = $request->input('externalUserId', 'default-user-id'); // Default user ID

        $url = "/resources/sdkIntegrations/levels/{$levelName}/websdkLink";
        $method = 'POST';

        $payload = json_encode([
            'externalUserId' => $externalUserId, // Required field
        ]);

        // String to sign
        $stringToSign = $timestamp . $method . $url . $payload;

        // Generate signature
        $signature = hash_hmac('sha256', $stringToSign, $secretKey);

        // Send the request
        $response = Http::withHeaders([
            'X-App-Token' => $appToken,
            'X-App-Access-Ts' => $timestamp,
            'X-App-Access-Sig' => $signature,
            'Content-Type' => 'application/json',
        ])->post('https://api.sumsub.com' . $url, json_decode($payload, true));

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'webSdkLink' => $response->json()['url'],
                'token' => $token,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $response->json(),
               
            ], $response->status());
        }
    }

    public function getApplicantData()
    {
        $user= auth()->user();

        $appToken = $this->appToken;
        $secretKey = $this->secretKey;
        $timestamp = time();
        $urlPath = 'resources/applicants/' . $user->applicant_id . '/one';
        $method = 'GET';

        // Generate signature
        $stringToSign = $timestamp . $method . '/' . $urlPath;
        $signature = hash_hmac('sha256', $stringToSign, $secretKey);

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', 'https://api.sumsub.com/' . $urlPath, [
                'headers' => [
                    'X-App-Token' => $appToken,
                    'X-App-Access-Ts' => $timestamp,
                    'X-App-Access-Sig' => $signature,
                    'accept' => 'application/json',
                ],
            ]);
    
            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);
    
            return response()->json([
                'status' => 'success',
                'data' => $data,
            ], 200);
    
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorResponse = $e->getResponse()->getBody()->getContents();
            return response()->json([
                'status' => 'error',
                'message' => 'Client error',
                'details' => $errorResponse,
            ], 400);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRequiredIdDocsStatus()
    {
        $user = auth()->user();

        $appToken = $this->appToken;
        $secretKey = $this->secretKey;
        $timestamp = time();
        $urlPath = 'resources/applicants/' . $user->applicant_id . '/requiredIdDocsStatus';
        $method = 'GET';

        // Generate signature
        $stringToSign = $timestamp . $method . '/' . $urlPath;
        $signature = hash_hmac('sha256', $stringToSign, $secretKey);

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', 'https://api.sumsub.com/' . $urlPath, [
                'headers' => [
                    'X-App-Token' => $appToken,
                    'X-App-Access-Ts' => $timestamp,
                    'X-App-Access-Sig' => $signature,
                    'accept' => 'application/json',
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ], 200);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorResponse = $e->getResponse()->getBody()->getContents();
            return response()->json([
                'status' => 'error',
                'message' => 'Client error',
                'details' => $errorResponse,
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
