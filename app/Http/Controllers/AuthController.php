<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 🔐 Inscription
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'in:admin,teacher,student',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'student',
        ]);

        return response()->json([
            'message' => 'Inscription réussie',
            'role' => $user->role
        ], 201);
    }

    // 🔐 Connexion avec session Sanctum
  
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Identifiants invalides'], 401);
    }

    // ✅ Générer un token aléatoire et le stocker dans la DB
    $token = Str::random(60);
$user->api_token = $token; 
    $user->save();

    return response()->json([
        'message' => 'Connexion réussie',
        'role' => $user->role,
        'name' => $user->name,
        'token' => $token // le vrai token brut
    ]);
}
    // 🔐 Déconnexion
public function logout(Request $request)
{
    $user = $request->user();
    if ($user) {
        $user->api_token = null; 
        $user->save();
    }

    return response()->json(['message' => 'Déconnexion réussie']);
}

    // 🔎 Récupérer l'utilisateur connecté
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
