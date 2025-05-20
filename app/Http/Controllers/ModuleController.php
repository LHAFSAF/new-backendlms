<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index(Request $request, $courseId)
    {
        $query = Module::where('course_id', $courseId)->with('resources');

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return $query->orderBy('order_index')->get();
    }

    public function show($id)
    {
        return Module::with('resources')->findOrFail($id);
    }

    public function store(Request $request, $courseId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'nullable|integer'
        ]);

        $module = Module::create([
            'course_id' => $courseId,
            'title' => $validated['title'],
            'order_index' => $validated['order_index'] ?? 0
        ]);

        return response()->json($module, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'nullable|integer'
        ]);

        $module = Module::findOrFail($id);
        $module->update($validated);

        return response()->json($module);
    }

    public function destroy($id)
    {
        $module = Module::findOrFail($id);
        $module->delete();

        return response()->json(['message' => 'Module supprimé avec succès']);
    }
}
