<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserActivationController extends Controller
{
    
    private const ACTIVATION_CODE = 'C4LL_C3NT3R_PBX_4CT1V4T10N_C0D3';

    public function activateUser(Request $request)
    {
        $request->validate([
            'user_email' => 'required|email|exists:v_users,user_email',
            'activation_code' => 'required|string'
        ]);

        $email = $request->input('user_email');
        $inputActivationCode = $request->input('activation_code');

        if ($inputActivationCode !== self::ACTIVATION_CODE) {
            return response()->json([
                'message' => 'Invalid activation code'
            ], 400);
        }

        $user = User::where('user_email', $email)->first();

        if ($user->user_enabled) {
            return response()->json([
                'message' => 'User is already activated'
            ], 400);
        }

        $user->user_enabled = true;
        $user->save();

        Log::info("User activated: {$email}");

        return response()->json([
            'message' => 'User activated successfully',
            'user' => $user
        ], 200);
    }
}