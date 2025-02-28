<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sumsub Verification</title>
    <script src="https://static.sumsub.com/idensic/static/sns-websdk-builder.js"></script>
</head>
<body>
    <h2>Identity Verification</h2>
    <button id="startVerification">Start Verification</button>

    <script>
        document.getElementById('startVerification').addEventListener('click', async function () {
            try {
                // Step 1: Create Applicant
                let applicantResponse = await fetch('/sumsub/create-applicant', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ externalUserId: 'test-user-' + Date.now() })
                });
                console.log('Applicant Created:', applicantResponse);
                let applicantData = await applicantResponse.json();
                let externalUserId = applicantData.id;

                // Step 2: Generate Access Token
                let tokenResponse = await fetch('/sumsub/generate-token', {
                    method: 'POST',
                    headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                         },
                    body: JSON.stringify({ externalUserId: externalUserId })
                });
                let tokenData = await tokenResponse.json();
                let accessToken = tokenData.token;

                // Step 3: Start Sumsub WebSDK
                let snsWebSdk = SNSWebSdk.init(accessToken)
                    .on('idCheck.onReady', () => console.log('Sumsub SDK Ready'))
                    .on('idCheck.onApplicantReviewed', (data) => console.log('Verification complete:', data))
                    .on('idCheck.onError', (error) => console.error('Sumsub Error:', error))
                    .build();

                snsWebSdk.launch('#startVerification');

            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>
