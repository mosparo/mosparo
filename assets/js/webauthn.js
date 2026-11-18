import { startRegistration, startAuthentication, browserSupportsWebAuthnAutofill } from '@simplewebauthn/browser';

async function canUseWebauthnAutofill()
{
    return await browserSupportsWebAuthnAutofill();
}

async function registerWebauthn(username, displayName, keyName, optionsPath, resultPath, onSuccess, onError)
{
    try {
        // 1. Fetch registration options from your server
        const optionsResponse = await fetch(optionsPath, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                username: username,
                displayName: displayName
            })
        });

        const options = await optionsResponse.json();
        if (optionsResponse.status === 401) {
            onError('Unauthorized', optionsResponse.status, options);
            return;
        }

        // 2. Start registration (handles encoding automatically)
        const attestationResponse = await startRegistration({ optionsJSON: options });
        attestationResponse.keyName = keyName;

        // 3. Send to server for validation
        const verificationResponse = await fetch(resultPath, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(attestationResponse)
        });

        const verificationResult = await verificationResponse.json();
        if (verificationResponse.status === 401) {
            onError('Unauthorized', verificationResponse.status, verificationResult);
            return;
        }

        if (verificationResult.status === 'ok') {
            onSuccess(verificationResult);
        } else {
            onError(verificationResult.errorMessage);
        }
    } catch (error) {
        if (console.error) {
            console.error('Registration failed:', error);
        }

        onError(error);
    }
}

async function authenticateWebauthn(lazy, username, optionsPath, resultPath, onStart, onSuccess, onError)
{
    try {
        // Autofill (conditional UI) is optional, if the browser does not support it, we do nothing
        if (lazy && !(await browserSupportsWebAuthnAutofill())) {
            return;
        }

        // 1. Fetch authentication options from server
        const optionsResponse = await fetch(optionsPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username
            })
        });

        const options = await optionsResponse.json();

        // 2. Start authentication (handles encoding and aborting of a pending autofill request automatically)
        const assertionResponse = await startAuthentication({
            optionsJSON: options,
            useBrowserAutofill: lazy
        });

        onStart();

        // 3. Send to server for validation
        const response = await fetch(resultPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(assertionResponse)
        });
        const responseData = await response.json();

        if (responseData.status === 'ok') {
            onSuccess(responseData);
        } else {
            onError(responseData.errorMessage);
        }
    } catch (error) {
        if (console.error) {
            console.error('Authentication failed:', error);
        }

        onError(error);
    }
}

window.canUseWebauthnAutofill = canUseWebauthnAutofill;
window.registerWebauthn = registerWebauthn;
window.authenticateWebauthn = authenticateWebauthn;
