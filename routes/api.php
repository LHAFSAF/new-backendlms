<?php
use App\Models\User;
use App\Notifications\ResetPasswordLink;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ResourceController;

use Illuminate\Support\Facades\DB;

Route::get('/charts/users-by-role', function () {
    return User::select('role', DB::raw('count(*) as total'))
        ->groupBy('role')
        ->get();
});

Route::get('/charts/users-per-month', function () {
    return DB::table('users')
        ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('COUNT(*) as total'))
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->get();
});

Route::get('/stats', function () {
    return response()->json([
        'users' => User::count(),
        'courses' => 57, // temporaire
        'certificates' => 312, // temporaire
        'revenue' => 4800 // temporaire
    ]);
});

Route::get('/users', function () {
    return User::all();
});


Route::get('/users', function (Request $request) {
    $query = User::query();

    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where('name', 'like', "%$search%");
    }

    if ($request->has('role')) {
        $query->where('role', $request->input('role'));
    }

    return $query->orderBy('created_at', 'desc')->get();
});


Route::delete('/users/{id}', function ($id) {
    $user = User::findOrFail($id);

    // Protection : empêcher la suppression d’un admin
    if ($user->role === 'admin') {
        return response()->json(['message' => 'Impossible de supprimer un administrateur.'], 403);
    }

    $user->delete();
    return response()->json(['message' => 'Utilisateur supprimé.']);
});


Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'Utilisateur introuvable.'], 404);
    }

    $token = Password::createToken($user);
    $user->notify(new ResetPasswordLink($token, $user->email));

    return response()->json(['message' => 'Lien de réinitialisation envoyé !']);
});

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => 'Mot de passe réinitialisé avec succès.'])
        : response()->json(['message' => 'Échec de la réinitialisation.'], 500);
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/courses', [CourseController::class, 'index']);
Route::post('/courses', [CourseController::class, 'store']);
Route::post('/courses/{id}/modules', [CourseController::class, 'addModule']);
Route::post('/modules/{id}/resources', [CourseController::class, 'addResource']);


Route::get('/modules/{id}/resources', [ResourceController::class, 'index']);
Route::post('/modules/{id}/resources', [ResourceController::class, 'store']);
Route::delete('/resources/{id}', [ResourceController::class, 'destroy']);
