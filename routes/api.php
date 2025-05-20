<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Notifications\ResetPasswordLink;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizResultController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;


///////////////////////////Route pour marquer une ressource terminée/////////////:::::::::::::
Route::post('/resources/{id}/complete', function ($id) {
    $user = request()->user();

    $user->completedResources()->syncWithoutDetaching([
        $id => ['completed_at' => now()]
    ]);

    return response()->json(['message' => 'Ressource marquée comme terminée']);
})->middleware('auth:api');
////////////////////////////////////////////////////////////////

Route::middleware('auth:api')->get('/courses/{id}/full-content', function ($id) {
    $course = Course::with('modules.resources')->findOrFail($id);
    return response()->json($course);
});
/////////////update -profile settings///////::::
Route::middleware('auth:api')->group(function () {
    Route::get('/profile', function (Request $request) {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // ✅ Retourne uniquement les infos nécessaires pour Settings.jsx
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    });

    Route::put('/profile', [ProfileController::class, 'update']);
});
///////////////////:::::S’inscrire au cours:::::::::::::::::::::::////////////////////////:
Route::post('/student/courses/{id}/start', function ($id) {
    $user = request()->user();

    $exists = \App\Models\CourseProgress::where('user_id', $user->id)
        ->where('course_id', $id)
        ->exists();

    if ($exists) {
        return response()->json(['message' => 'Déjà inscrit'], 200);
    }

    \App\Models\CourseProgress::create([
        'user_id' => $user->id,
        'course_id' => $id,
        'progress' => 0,
    ]);

    return response()->json(['message' => 'Cours marqué comme en cours']);
})->middleware('auth:api');
////////////////////////////////////////////////////////////////////////////////////////////:

////////////////////////////////////////pour récupérer les cours en cours////////////////////////
Route::get('/student/courses/in-progress', function () {
    $user = request()->user();

    $progress = \App\Models\CourseProgress::with('course')
        ->where('user_id', $user->id)
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->course->id,
                'title' => $item->course->title,
                'description' => $item->course->description,
                'image' => $item->course->image ?? 'https://via.placeholder.com/300x200',
                'progress' => $item->progress,
            ];
        });

    return response()->json($progress);
})->middleware('auth:api');

//
// 📊 Statistiques & Graphiques
//
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
        'courses' => Course::count(),
        'certificates' => 312, // temporaire
        'revenue' => 4800      // temporaire
    ]);
});
//////////////////////////////progress.................///////////////////////////

Route::middleware('auth:api')->group(function () {
    Route::post('/courses/{id}/enroll', [ProgressController::class, 'enroll']);
    Route::get('/student/courses/in-progress', [ProgressController::class, 'inProgress']);
    Route::post('/modules/{id}/complete', [ProgressController::class, 'completeModule']);
});

/////////////////////////////////////////////////////////////////////////////:
//
// 👥 Utilisateurs
//
Route::get('/users', function (Request $request) {
    $query = User::query();

    if ($request->has('search')) {
        $query->where('name', 'like', "%" . $request->input('search') . "%");
    }

    if ($request->has('role')) {
        $query->where('role', $request->input('role'));
    }

    return $query->orderBy('created_at', 'desc')->get();
});

Route::delete('/users/{id}', function ($id) {
    $user = User::findOrFail($id);

    if ($user->role === 'admin') {
        return response()->json(['message' => 'Impossible de supprimer un administrateur.'], 403);
    }

    $user->delete();
    return response()->json(['message' => 'Utilisateur supprimé.']);
});

//
// 🔐 Authentification
//
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);


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
            $user->forceFill(['password' => Hash::make($password)])->save();
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => 'Mot de passe réinitialisé avec succès.'])
        : response()->json(['message' => 'Échec de la réinitialisation.'], 500);
});

//
// 📚 Cours
//
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::post('/courses', [CourseController::class, 'store']);
Route::put('/courses/{id}', [CourseController::class, 'update']);
Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

//
// 🧱 Modules
//
Route::get('/courses/{courseId}/modules', [ModuleController::class, 'index']);
Route::get('/modules/{id}', [ModuleController::class, 'show']);
Route::post('/courses/{courseId}/modules', [ModuleController::class, 'store']);
Route::put('/modules/{id}', [ModuleController::class, 'update']);
Route::delete('/modules/{id}', [ModuleController::class, 'destroy']);
Route::get('/modules', function () {
    return Module::all();
});

//
// 📎 Ressources
//
Route::get('/modules/{moduleId}/resources', [ResourceController::class, 'index']);
Route::get('/resources/{id}', [ResourceController::class, 'show']);
Route::post('/modules/{moduleId}/resources', [ResourceController::class, 'store']);
Route::put('/resources/{id}', [ResourceController::class, 'update']);
Route::delete('/resources/{id}', [ResourceController::class, 'destroy']);

//
// 📋 Quiz
//
Route::get('/quizzes', [QuizController::class, 'index']);
Route::post('/quizzes', [QuizController::class, 'store']);
Route::get('/quizzes/{id}', [QuizController::class, 'show']);
Route::put('/quizzes/{id}', [QuizController::class, 'update']);
Route::delete('/quizzes/{id}', [QuizController::class, 'destroy']);

Route::post('/modules/{id}/quizzes', [QuizController::class, 'storeByModule']);
Route::get('/modules/{id}/quizzes', [QuizController::class, 'byModule']);

Route::get('/quizzes/module/{moduleId}', [QuizController::class, 'byModule']);
Route::get('/quizzes/course/{courseId}', [QuizController::class, 'byCourse']);

Route::get('/quizzes/{id}/questions', [QuizController::class, 'getQuestions']);
Route::post('/quizzes/{id}/questions', [QuizController::class, 'addQuestion']);
Route::put('/questions/{id}', [QuestionController::class, 'update']);
Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

// POST pour soumettre les réponses au quiz
Route::post('/quizzes/{id}/submit', [QuizController::class, 'submitAnswers']);

//
// 📈 Résultats des Quiz
//
Route::post('/quiz-results', [QuizResultController::class, 'store']);
Route::get('/quizzes/{quizId}/results', [QuizResultController::class, 'byQuiz']);
////////////////////:::user

Route::middleware('auth:sanctum')->put('/user', [UserController::class, 'update']);

//////////////////////////bar d'avancement////////////////////////
Route::middleware('auth:api')->post('/resources/{id}/complete', function ($id) {
    $user = request()->user();
    $user->completedResources()->syncWithoutDetaching([$id]);

    // Calcul de la progression (en %)
    $resource = \App\Models\Resource::findOrFail($id);
    $courseId = $resource->module->course_id;

    $total = \App\Models\Resource::whereHas('module', fn($q) => $q->where('course_id', $courseId))->count();
    $done = $user->completedResources()->whereHas('module', fn($q) => $q->where('course_id', $courseId))->count();
    $percent = $total > 0 ? round(($done / $total) * 100) : 0;

    \App\Models\CourseProgress::updateOrCreate(
        ['user_id' => $user->id, 'course_id' => $courseId],
        ['progress' => $percent]
    );

    return response()->json(['message' => 'Terminé', 'progress' => $percent]);
});
Route::middleware('auth:api')->get('/me/completed-modules', [ProgressController::class, 'completedModules']);

////////////////////////////////////////////
Route::middleware('auth:api')->get('/me/completed-resources', function () {
    $user = request()->user();
    return $user->completedResources()->pluck('resources.id');
});
/////////////////afficher details user///////////////////////////
Route::get('/admin/users/{id}', function ($id) {
    return \App\Models\User::findOrFail($id);
});

Route::get('/admin/users/{id}/progress', function ($id) {
    return \App\Models\CourseProgress::with('course')
        ->where('user_id', $id)
        ->get()
        ->map(function ($p) {
            return [
                'id' => $p->course->id,
                'title' => $p->course->title,
                'progress' => $p->progress,
            ];
        });
});
//////////////quiz result//////////
Route::middleware('auth:api')->get('/quizzes/{quizId}/my-result', [QuizResultController::class, 'myResult']);
