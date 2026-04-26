<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'email' => 'required|email|unique:users',
            'telefono' => 'nullable|string',
            'password' => 'required|string|min:3'
        ]);

        $user = User::create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? NULL,
            'password' => Hash::make($data['password']),
        ]);

        return response()->json(['message' => 'usuario creado'], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        //Validamos que el usuario exista y la constraseña 
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectos'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente y token eliminado'
        ], 200);
    }


    public function redirectToGoogle()
    {
        // Esto envía al usuario a Google
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            // Esto recibe los datos de Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Creamos el usuario en tu base de datos login_api_db
            $user = User::updateOrCreate([
                'email' => $googleUser->email,
            ], [
                'nombre' => $googleUser->name,
                'google_id' => $googleUser->id,
                'telefono' => null,
                'password' => Hash::make(Str::random(16)),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirigimos al Dashboard de Next.js
            return redirect('http://localhost:3000/dashboard?token=' . $token);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
