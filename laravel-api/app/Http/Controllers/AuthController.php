<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Mail\EmailVerificationCode;
use App\Mail\EmailVerificationOTP;
use App\Mail\PasswordResetCode;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed|min:8',
                'phone_number' => 'nullable|string|max:20',
            ]);

            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpiresAt = Carbon::now()->addMinutes(10);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'phone_number' => $validated['phone_number'] ?? null,
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
                'email_verified_at' => null,
                'is_verified' => false,
            ]);

            try {
                $user->assignRole('User');
            } catch (Exception $e) {
                Log::error('Error assigning role.', ['error' => $e->getMessage()]);
            }

            try {
                Mail::to($user->email)->send(new EmailVerificationOTP($otp, $user->name));
            } catch (Exception $e) {
                Log::error('Error sending OTP email.', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Registration successful but email could not be sent. Please contact support.',
                    'user' => [
                        'email' => $user->email,
                        'name' => $user->name,
                    ]
                ], 201);
            }

            return response()->json([
                'message' => 'Registration successful! Please check your email for the OTP verification code.',
                'user' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ]
            ], 201);
        } catch (Exception $e) {
            Log::error('Registration failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('email_verification_code', $request->code)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid verification code'], 422);
        }

        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->is_verified = true;
        $user->save();

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified',
                'already_verified' => true
            ], 200);
        }

        if (!$user->otp) {
            return response()->json([
                'message' => 'No OTP found. Please request a new one.'
            ], 422);
        }

        // Check if OTP has expired
        if ($user->otp_expires_at && Carbon::parse($user->otp_expires_at)->isPast()) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new one.',
                'expired' => true
            ], 422);
        }

        // Verify OTP
        if ($user->otp !== $request->otp) {
            return response()->json([
                'message' => 'Invalid OTP. Please try again.'
            ], 422);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->is_verified = true;
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        // Create token for auto-login after verification
        $token = $user->createToken('api-token')->plainTextToken;
        $role = $user->roles()->pluck('name')->first();

        return response()->json([
            'message' => 'Email verified successfully! You can now login.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'phone_number' => $user->phone_number,
                'address' => $user->address,
                'profile_picture' => $user->profile_picture,
                'status' => $user->status,
                'role' => $role,
                'email_verified_at' => $user->email_verified_at,
                'is_verified' => $user->is_verified,
            ]
        ], 200);
    }

    public function resendOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified'
            ], 200);
        }

        // Check if we should rate limit OTP requests (e.g., 1 minute between requests)
        if ($user->otp_expires_at && Carbon::parse($user->otp_expires_at)->subMinutes(9)->isFuture()) {
            $remainingSeconds = Carbon::now()->diffInSeconds(Carbon::parse($user->otp_expires_at)->subMinutes(9));
            return response()->json([
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
                'retry_after' => $remainingSeconds
            ], 429);
        }

        // Generate new OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = Carbon::now()->addMinutes(10);

        $user->otp = $otp;
        $user->otp_expires_at = $otpExpiresAt;
        $user->save();

        // Send OTP email
        try {
            Mail::to($user->email)->send(new EmailVerificationOTP($otp, $user->name));

            return response()->json([
                'message' => 'A new OTP has been sent to your email.'
            ], 200);
        } catch (Exception $e) {
            Log::error('Error sending OTP email.', [
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to send OTP. Please try again later.'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'nullable|email',
            'password' => 'nullable|string',
        ]);

        if (!$request->email && !$request->password) {
            return response()->json(['message' => 'Email or Password is required'], 422);
        }

        $user = null;

        if ($request->email) {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'Invalid email'], 401);
            }
            if ($request->password && !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid password'], 401);
            }
        }

        if ($request->password && !$request->email) {
            $user = User::whereNotNull('password')
                ->get()
                ->first(function ($u) use ($request) {
                    return Hash::check($request->password, $u->password);
                });

            if (!$user) {
                return response()->json(['message' => 'Invalid password'], 401);
            }
        }

        if (!$user->email_verified_at && !$user->google_id) {
            return response()->json([
                'message' => 'Please verify your email before logging in. Check your inbox for the OTP code.',
                'email_not_verified' => true,
                'email' => $user->email
            ], 403);
        }

        if (!$user->status) {
            return response()->json([
                'res' => 'error',
                'message' => 'Your account has been blocked by the administrator. Please contact support for assistance.',
                'error' => 'account_blocked'
            ], 403);
        }

        $role = $user->roles()->pluck('name')->first();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'phone_number'      => $user->phone_number,
                'address'           => $user->address,
                'profile_picture'   => $user->profile_picture,
                'status'            => $user->status,
                'role'              => $role,
                'email_verified_at' => $user->email_verified_at,
                'is_verified'       => $user->is_verified,
                'created_at'        => $user->created_at,
                'updated_at'        => $user->updated_at,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles', 'permissions');

        return response()->json([
            'user' => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'phone_number'      => $user->phone_number,
                'address'           => $user->address,
                'profile_picture'   => $user->profile_picture,
                'status'            => $user->status,
                'role'              => $user->getRoleNames()->first(),
                'email_verified_at' => $user->email_verified_at,
                'is_verified'       => $user->is_verified,
                'created_at'        => $user->created_at,
                'updated_at'        => $user->updated_at,
            ]
        ]);
    }

    public function allUser(Request $request)
    {
        $items = User::whereHas('roles', function ($q) {
            $q->where('name', 'User');
        })->get(['id', 'name', 'email', 'profile_picture', 'phone_number', 'address', 'status']);

        return response()->json($items);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $existing = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if ($existing && \Carbon\Carbon::parse($existing->created_at)->addMinutes(20)->isFuture()) {
            $remaining = \Carbon\Carbon::parse($existing->created_at)->addMinutes(20)->diffForHumans(null, true);
            return response()->json([
                'message' => "You can request a new code after {$remaining}."
            ], 429);
        }

        $code = rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => $code,
                'created_at' => now(),
                'expires_at' => now()->addMinutes(15),
            ]
        );

        Mail::to($user->email)->send(new PasswordResetCode($code));

        return response()->json(['message' => 'Password reset code sent to your email']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'code'     => 'required|string',
            'password' => 'required|confirmed|min:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid reset code'], 422);
        }

        if ($record->expires_at && $record->expires_at < now()) {
            return response()->json(['message' => 'Reset code has expired'], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successful']);
    }

    public function googleLogin(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
            ]);

            $httpClient = new \GuzzleHttp\Client([
                'verify' => base_path('cacert.pem')
            ]);

            $client = new \Google_Client([
                'client_id' => config('services.google.client_id')
            ]);
            $client->setHttpClient($httpClient);

            $payload = $client->verifyIdToken($request->token);

            if (!$payload) {
                return response()->json([
                    'message' => 'Invalid Google token'
                ], 401);
            }

            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'];
            $picture = $payload['picture'] ?? null;

            $user = User::where('email', $email)->first();

            if ($user) {
                if (!$user->google_id) {
                    $user->google_id = $googleId;
                    $user->save();
                }

                if (!$user->email_verified_at) {
                    $user->email_verified_at = now();
                    $user->is_verified = true;
                    $user->save();
                }
            } else {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'profile_picture' => $picture,
                    'email_verified_at' => now(),
                    'is_verified' => true,
                    'status' => true,
                    'password' => bcrypt(Str::random(32)),
                ]);

                try {
                    $user->assignRole('User');
                } catch (Exception $e) {
                    Log::error('Error assigning role to Google user.', ['error' => $e->getMessage()]);
                }
            }

            if (!$user->status) {
                return response()->json([
                    'res' => 'error',
                    'message' => 'Your account has been blocked by the administrator. Please contact support for assistance.',
                    'error' => 'account_blocked'
                ], 403);
            }

            $role = $user->roles()->pluck('name')->first();
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'address' => $user->address,
                    'profile_picture' => $user->profile_picture,
                    'status' => $user->status,
                    'role' => $role,
                    'email_verified_at' => $user->email_verified_at,
                    'is_verified' => $user->is_verified,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Google login failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Google login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
