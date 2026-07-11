<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Demo\ProvisionDemoSandbox;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($data);

        return response()->json([
            'user' => $user->only('id', 'name', 'email'),
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(Request $request, ProvisionDemoSandbox $demo): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api');

        $payload = [
            'user' => $user->only('id', 'name', 'email'),
            'token' => $token->plainTextToken,
        ];

        if ($user->email === config('demo.email')) {
            $sandbox = $demo->handle((int) $token->accessToken->getKey());

            if ($sandbox !== null) {
                $payload['sandbox_tournament_id'] = $sandbox->id;
            }
        }

        return response()->json($payload);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->only('id', 'name', 'email'));
    }
}
