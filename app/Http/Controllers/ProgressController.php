<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseProgress;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    // ✅ Inscrire l'utilisateur à un cours
    public function enroll(Request $request, $id)
    {
        $user = Auth::user();

        $exists = CourseProgress::where('user_id', $user->id)
            ->where('course_id', $id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Déjà inscrit'], 200);
        }

        CourseProgress::create([
            'user_id' => $user->id,
            'course_id' => $id,
            'progress' => 0,
        ]);

        return response()->json(['message' => 'Cours démarré avec succès']);
    }

    // ✅ Récupérer tous les cours en cours de l’utilisateur
    public function inProgress(Request $request)
    {
        $user = Auth::user();

        $progress = CourseProgress::with('course')
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
    }

    // ✅ Marquer un module comme terminé et mettre à jour la progression du cours
    public function completeModule(Request $request, $id)
    {
        $user = Auth::user();

        // Évite les doublons
        if ($user->modules()->where('module_id', $id)->exists()) {
            return response()->json(['message' => 'Module déjà terminé'], 200);
        }

        // Associe le module à l’utilisateur
        $user->modules()->attach($id);

        // Mise à jour de la progression
        $module = Module::findOrFail($id);
        $courseId = $module->course_id;

        $total = Module::where('course_id', $courseId)->count();
        $completed = $user->modules()->where('course_id', $courseId)->count();

        $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

        CourseProgress::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $courseId],
            ['progress' => $percent]
        );

        return response()->json([
            'message' => 'Module complété avec succès',
            'progress' => $percent
        ]);
    }

    // ✅ Renvoyer les IDs des modules complétés
    public function completedModules(Request $request)
    {
        $user = Auth::user();

        $completed = $user->modules()->pluck('modules.id');

        return response()->json($completed);
    }
}
