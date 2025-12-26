<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Show the API key settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // Check if user has an API key set (don't expose the actual key)
        $hasApiKey = !empty($user->api_key);
        
        // Show masked version if key exists
        $maskedKey = null;
        if ($hasApiKey) {
            try {
                $decrypted = Crypt::decryptString($user->api_key);
                $maskedKey = substr($decrypted, 0, 7) . '...' . substr($decrypted, -4);
            } catch (\Exception $e) {
                $maskedKey = '••••••••••••';
            }
        }

        return Inertia::render('settings/ApiKey', [
            'hasApiKey' => $hasApiKey,
            'maskedKey' => $maskedKey,
        ]);
    }

    /**
     * Update the user's API key.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'api_key' => ['required', 'string', 'min:10'],
        ]);

        $user = $request->user();
        
        // Encrypt the API key before storing
        $user->api_key = Crypt::encryptString($request->api_key);
        $user->save();

        return back()->with('status', 'api-key-updated');
    }

    /**
     * Remove the user's API key.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->api_key = null;
        $user->save();

        return back()->with('status', 'api-key-removed');
    }
}
