<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'error' => 'USER_NOT_FOUND',
                'email' => $email,
            ], 401);
        }

        if (! Hash::check($password, $user->password)) {
            return response()->json([
                'error' => 'PASSWORD_MISMATCH',
            ], 401);
        }

        $token = $user->createToken('nextjs-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }
}
