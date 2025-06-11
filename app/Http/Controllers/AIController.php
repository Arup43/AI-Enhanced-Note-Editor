<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AIController extends Controller
{
    public function enhance(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'action' => 'required|in:summarize,improve,generate_tags',
        ]);

        $content = $request->input('content');
        $action = $request->input('action');

        $prompt = $this->getPromptForAction($action, $content);

        // Real API call
        $apiKey = config('openai.api_key');
        $model = config('openai.model');

        if (empty($apiKey)) {
            return response()->json(['error' => 'OpenAI API key not configured'], 500);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'stream' => false,
        ]);

        if ($response->successful()) {
            return response()->json(['result' => $response->json('choices.0.message.content')]);
        } else {
            return response()->json(['error' => 'Failed to get response from OpenAI: ' . $response->status()], 500);
        }
    }

    private function getPromptForAction(string $action, string $content): string
    {
        return match ($action) {
            'summarize' => "Please provide a concise summary of the following text:\n\n{$content}",
            'improve' => "Please improve the following text for clarity, grammar, and style:\n\n{$content}",
            'generate_tags' => "Generate 5-10 relevant tags for the following text (return only the tags separated by commas):\n\n{$content}",
        };
    }
}
