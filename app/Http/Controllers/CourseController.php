<?php
namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use App\Models\Resource;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // 📋 Afficher tous les cours avec leurs modules et ressources
    public function index()
    {
        return Course::with(['teacher', 'modules.resources'])->orderBy('created_at', 'desc')->get();
    }

    // ➕ Créer un nouveau cours
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $course = Course::create($validated);
        return response()->json($course, 201);
    }

    // ➕ Ajouter un module à un cours
    public function addModule(Request $request, $courseId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'nullable|integer'
        ]);

        $module = Module::create([
            'title' => $validated['title'],
            'order_index' => $validated['order_index'] ?? 0,
            'course_id' => $courseId,
        ]);

        return response()->json($module, 201);
    }

    // ➕ Ajouter une ressource à un module
    public function addResource(Request $request, $moduleId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,pdf,link,text',
            'content' => 'nullable|string',
        ]);

        $resource = Resource::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $validated['content'],
            'module_id' => $moduleId,
        ]);

        return response()->json($resource, 201);
    }
}
