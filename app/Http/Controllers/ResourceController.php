<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    // 📋 Lister les ressources d’un module
    public function index($moduleId)
    {
        return Resource::where('module_id', $moduleId)
            ->orderBy('created_at')
            ->get();
    }

    // ➕ Ajouter une ressource
    public function store(Request $request, $moduleId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,pdf,link,text,image,youtube',
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:mp4,pdf,jpg,jpeg,png|max:20480',
        ]);

        $resource = new Resource();
        $resource->module_id = $moduleId;
        $resource->title = $validated['title'];
        $resource->type = $validated['type'];

        // 🗂️ Si fichier à uploader
        if ($request->hasFile('file') && in_array($validated['type'], ['video', 'pdf', 'image'])) {
            $path = $request->file('file')->store('resources', 'public');
            $resource->content = '/storage/' . $path;
        } else {
            // 🌐 Cas texte, lien, YouTube
            $resource->content = $validated['content'] ?? null;
        }

        $resource->save();

        return response()->json($resource, 201);
        dd($validated);

    }

    // ❌ Supprimer une ressource
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
