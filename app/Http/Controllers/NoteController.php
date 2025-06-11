<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $notes = Note::where('user_id', $user->id)
            ->select(['id', 'title', 'content', 'tags', 'created_at', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'title' => $note->title,
                    'content' => substr($note->content, 0, 200) . (strlen($note->content) > 200 ? '...' : ''),
                    'tags' => $note->tags,
                    'created_at' => $note->created_at->format('M d, Y H:i'),
                    'updated_at' => $note->updated_at->format('M d, Y H:i'),
                ];
            });

        return Inertia::render('dashboard', [
            'notes' => $notes,
        ]);
    }

    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        return Inertia::render('note-editor', [
            'note' => [
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content,
                'tags' => $note->tags,
                'created_at' => $note->created_at->format('M d, Y H:i'),
                'updated_at' => $note->updated_at->format('M d, Y H:i'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('note-editor', [
            'note' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|array',
        ]);

        $note = Note::create([
            'user_id' => Auth::id(),
            ...$validated
        ]);

        // Clear the dashboard cache to ensure fresh data on next visit
        return redirect()->route('notes.edit', $note->id)->with([
            'dashboardInvalidated' => true
        ]);
    }

    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|array',
        ]);

        $note->update($validated);

        // Check if it's an Inertia request
        if ($request->header('X-Inertia')) {
            return back()->with([
                'note' => [
                    'id' => $note->id,
                    'title' => $note->title,
                    'content' => $note->content,
                    'tags' => $note->tags,
                    'created_at' => $note->created_at->format('M d, Y H:i'),
                    'updated_at' => $note->updated_at->format('M d, Y H:i'),
                ]
            ]);
        }

        // For regular AJAX requests, return JSON
        return response()->json(['note' => $note]);
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);

        $note->delete();

        // Use Inertia::location to force a full page reload and clear all cached data
        return \Inertia\Inertia::location(route('dashboard'));
    }
}
