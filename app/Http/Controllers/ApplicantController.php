<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Services\SumsubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class ApplicantController extends Controller
{
    protected $sumsubService;
    protected $sumsubController;

    public function __construct(SumsubService $sumsubService, SumsubController $sumsubController)
    {
        $this->sumsubService = $sumsubService;
        $this->sumsubController = $sumsubController;
    }

    public function index()
    {
        $user = Auth::user();
        $getApplicantData = $this->sumsubController->getApplicantData();
        $applicantData = $getApplicantData->getData(true);
        $imagesIdn = [];

    //    dd($applicantData);
        if($applicantData['data']['review']['attemptCnt'] != 0) {
            $getRequiredIdDocsStatus = $this->sumsubController->getRequiredIdDocsStatus();
            $requiredIdDocsStatus = $getRequiredIdDocsStatus->getData(true);
            $imagesId = $requiredIdDocsStatus['data']['IDENTITY']['imageIds'];
        }
        
        return view('sumsub', compact('user', 'applicantData'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'external_user_id' => 'required|unique:applicants,external_user_id',
        ]);
    
        $externalUserId = $request->input('external_user_id');
    
        $sumsubResponse = $this->sumsubService->createApplicant($externalUserId);

        if (isset($sumsubResponse['id'])) {
            // Store the applicant details in your database
            Applicant::create([
                'external_user_id' => $externalUserId,
                'applicant_id' => $sumsubResponse['id'],
            ]);
    
            return redirect()->route('applicant.index')->with('success', 'Applicant created successfully!');
        }
    
        // Extract error message and code
        $errorMessage = $sumsubResponse['error'] ?? 'Failed to create applicant. Please try again.';
        $errorCode = $sumsubResponse['code'] ?? 'Unknown';
    
        return back()->withErrors("Error {$errorCode}: {$errorMessage}");
    
    }

    public function getWebSdkToken($externalUserId)
    {
        $appToken = 'your_app_token'; // Replace with your actual app token
        $secretKey = 'your_secret_key'; // Replace with your actual secret key
        $levelName = 'id-only'; // Replace with your desired level name
        $timestamp = time();

        // Create the signature
        $method = 'POST';
        $uri = '/resources/accessTokens';
        $body = json_encode([
            'userId' => $externalUserId,
            'levelName' => $levelName,
        ]);

        $stringToSign = $timestamp . $method . $uri . $body;
        $signature = hash_hmac('sha256', $stringToSign, $secretKey);

        // Make the API request
        $response = Http::withHeaders([
            'X-App-Token' => $appToken,
            'X-App-Access-Ts' => $timestamp,
            'X-App-Access-Sig' => $signature,
            'Content-Type' => 'application/json',
        ])->post('https://api.sumsub.com/resources/accessTokens', [
            'userId' => $externalUserId,
            'levelName' => $levelName,
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to generate WebSDK token'], 500);
    }

    public function showWebSdk($externalUserId)
    {
        // Pass user information to the view
        return view('applicants.websdk', [
            'externalUserId' => $externalUserId,
            'email' => 'user@example.com', // Replace with actual user email
        ]);
    }


}
