
@extends('layouts.app')
   
@section('content')
    <h1>Submit Id</h1>
            
    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <!-- Sumsub WebSDK Integration Script -->
    @if(!$applicantData['data']['review']['attemptCnt'])
        <div id="sumsub-websdk-container"></div>
    @else
        <p>You have already submitted your ID</p>
        <h3>Scanned Info</h3>
        <div class="container mt-4">
            <div class="card shadow-sm p-4">
                <h3 class="mb-3 text-primary">Applicant Details</h3>
                <form>
                    <div class="row">
                        @foreach ($applicantData['data']['info'] as $key => $info)
                            @if ($key === 'idDocs')
                                <h4 class="mt-4 text-secondary">ID Details</h4>
                                @foreach ($applicantData['data']['info'][$key] as $idInfos)
                                    @foreach ($idInfos as $idKey => $idInfo)
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label fw-bold">{{ ucfirst($idKey) }}</label>
                                            <input type="text" class="form-control" value="{{ is_array($idInfo) ? json_encode($idInfo) : $idInfo }}" disabled>
                                        </div>
                                    @endforeach
                                @endforeach
                            @elseif($key === 'addresses')
                                <h4 class="mt-4 text-secondary">Address</h4>
                                @foreach ($applicantData['data']['info'][$key] as $addresses)
                                    @foreach ($addresses as $addKey => $address)
                                        <div class="mb-3 col-md-6">
                                            <label class="form-label fw-bold">{{ ucfirst($addKey) }}</label>
                                            <input type="text" class="form-control" value="{{ is_array($address) ? json_encode($address) : $address }}" disabled>
                                        </div>
                                    @endforeach
                                @endforeach   
                            @else
                                <div class="mb-3 col-md-6">
                                    <label class="form-label fw-bold">{{ ucfirst($key) }}</label>
                                    <input type="text" class="form-control" value="{{ is_array($info) ? json_encode($info) : $info }}" disabled>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </form>
            </div>
        
            <div class="card shadow-sm p-4 mt-4">
                <h3 class="text-primary">Review Result</h3>
                <p class="fw-bold">{{ $applicantData['data']['review']['reviewResult']['reviewAnswer'] }}</p>
        
                <h3 class="text-primary">Review Status</h3>
                <p class="fw-bold">{{ $applicantData['data']['review']['reviewStatus'] }}</p>
            </div>
        </div>
    @endif
    <!-- Include Sumsub WebSDK Script -->
    <script src = "https://static.sumsub.com/idensic/static/sns-websdk-builder.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const externalUserId = "{{ $user->external_id }}"; 
            const generateWebSdkUrl = "{{ url('/generate-websdk-link') }}";
            const attemptCnt = {{ $applicantData['data']['review']['attemptCnt'] }};

            if (!attemptCnt) {
                fetch(generateWebSdkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        levelName: 'id-only', // Adjust the level name if needed
                        externalUserId: externalUserId,
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Received WebSDK Token:', data.token);

                    // Ensure the Sumsub WebSDK script is fully loaded before initializing
                    console.log('Sumsub WebSDK Script Loaded');

                        
                        let snsWebSdkInstance = snsWebSdk.init(
                            data.token,
                            () => this.getNewAccessToken()
                        )
                        
                            .withOptions({ 
                                    lang: 'en',
                                    flowName: 'document-verification',
                                    showWelcomeScreen: true, // Show a welcome screen
                                    camera: {
                                        useBackCamera: true, // Use back camera for scanning
                                        enableDocumentAutoCapture: true // Enable scanner
                                    }
                                })
                            .on('idCheck.onReady', () => console.log('Sumsub WebSDK Ready'))
                            .on('idCheck.onApplicantReviewed', (data) => console.log('Verification Complete:', data))
                            .on('idCheck.onError', (error) => console.error('Sumsub SDK Error:', error))
                            .on('idCheck.onDocSubmitted', (data) => {
                                console.log('📄 Document Submitted:', data);
                            })
                            .on('idCheck.onDocAutoCaptured', (data) => {
                                console.log('📸 Document Auto Captured:', data);
                            })
                            .on('idCheck.onExtractedData', (data) => {
                                console.log('🔠 OCR Extracted Data:', data);
                            })
                            .build();

                        snsWebSdkInstance.launch('#sumsub-websdk-container');

                })
                .catch(error => console.error('Error fetching WebSDK link:', error));
            }
        });
    </script>
@endsection