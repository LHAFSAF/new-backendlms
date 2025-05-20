<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['teacher', 'modules.resources']);

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function show($id)
    {
        $course = Course::with(['teacher', 'modules.resources'])->findOrFail($id);

        foreach ($course->modules as $module) {
            if (!isset($module->resources)) {
                $module->resources = [];
            }
        }

        return response()->json($course);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'teacher_id'  => 'nullable|exists:users,id',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('courses', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $course = Course::create($validated);
        return response()->json($course, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'teacher_id'  => 'nullable|exists:users,id',
            'image'       => 'nullable|image|max:2048',
        ]);

        $course = Course::findOrFail($id);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('courses', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $course->update($validated);
        return response()->json($course);
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Cours supprimé avec succès']);
    }
}
