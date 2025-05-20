<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function index(Request $request, $moduleId)
    {
        $query = Resource::where('module_id', $moduleId);

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return $query->orderBy('created_at')->get();
    }

    public function store(Request $request, $moduleId)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'type'    => 'required|in:video,pdf,link,text,image,youtube',
            'content' => 'nullable|string',
            'file'    => 'nullable|file|mimes:mp4,pdf,jpg,jpeg,png|max:102400',
        ]);

        $resource = new Resource();
        $resource->module_id = $moduleId;
        $resource->title = $validated['title'];
        $resource->type = $validated['type'];

        if (
            $request->hasFile('file') &&
            in_array($validated['type'], ['video', 'pdf', 'image'])
        ) {
            $path = $request->file('file')->store('resources', 'public');
            $resource->content = '/storage/' . $path;
        } elseif (isset($validated['content'])) {
            $resource->content = $validated['content'];
        } else {
            return response()->json(['message' => 'Aucun contenu fourni.'], 422);
        }

        $resource->save();

        return response()->json($resource, 201);
    }

    public function show($id)
    {
        return Resource::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);

        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'type'    => 'required|in:video,pdf,link,text,image,youtube',
            'content' => 'nullable|string',
            'file'    => 'nullable|file|mimes:mp4,pdf,jpg,jpeg,png|max:102400',
        ]);

        if (
            $request->hasFile('file') &&
            in_array($validated['type'], ['video', 'pdf', 'image'])
        ) {
            $path = $request->file('file')->store('resources', 'public');
            $validated['content'] = '/storage/' . $path;
        }

        $resource->update($validated);

        return response()->json($resource);
    }

    public function destroy($id)
    {
        $resource = Resource::findOrFail($id);

        if (in_array($resource->type, ['pdf', 'image', 'video']) && $resource->content && str_starts_with($resource->content, '/storage/')) {
            $path = str_replace('/storage/', '', $resource->content);
            Storage::disk('public')->delete($path);
        }

        $resource->delete();

        return response()->json(['message' => 'Ressource supprimée']);
    }
}
