<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Handle registration / account activation.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|string|max:50',
            'work_email' => 'required|email|max:255|exists:employees,work_email',
            'password' => 'required|string|min:8|confirmed',
            'invitation_code' => 'required|string|max:32|exists:invitation_codes,code,is_used,false',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        // Find employee and check no existing user
        $employee = \DB::table('employees')
            ->where('work_email', $request->work_email)
            ->first();

        $existingUser = User::where('email', $request->work_email)->first();
        if ($existingUser) {
            return ApiResponse::error('Akun dengan email ini sudah terdaftar.', 409);
        }

        // Create user
        $user = User::create([
            'name' => $employee->name ?? $request->work_email,
            'email' => $request->work_email,
            'password' => Hash::make($request->password),
        ]);

        // Mark invitation code as used
        \DB::table('invitation_codes')
            ->where('code', $request->invitation_code)
            ->update(['is_used' => true, 'used_at' => now()]);

        $token = $user->createToken('mobile')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 'Registrasi berhasil. Silakan gunakan token untuk akses selanjutnya.');
    }

    /**
     * Handle login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Kredensial tidak valid.', 401);
        }

        $deviceName = $request->device_name ?? request()->userAgent();
        $token = $user->createToken($deviceName)->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 'Login berhasil.');
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    /**
     * Revoke current token (logout).
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return ApiResponse::success(null, 'Logout berhasil.');
    }
}
