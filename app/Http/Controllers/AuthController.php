<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register (Request $request){
        $data = $request->validate([
            'nombre' => 'required|string',
            'email' => 'required|email|unique:users',
            'telefono' => 'nullable|string',
            'password' => 'required|confirmed'
        ]);

        $user = User::create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? NULL, 
            'password' => Hash::make($data['password']),
        ]);

        return response()->json(['message' => 'usuario creado'], 201);
    }

    public function login (Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'    
        ]);

        $user = User::where('email', $request->email)->first();

        //Validamos que el usuario exista y la constraseña 
        if (!$user || !Hash::check($request->password, $user->password)){
            return response()->json(['message' => 'Credenciales incorrectos'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
