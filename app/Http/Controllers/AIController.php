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

        return new StreamedResponse(function () use ($prompt) {
            $this->streamOpenAIResponse($prompt);
        }, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    private function getPromptForAction(string $action, string $content): string
    {
        return match ($action) {
            'summarize' => "Please provide a concise summary of the following text:\n\n{$content}",
            'improve' => "Please improve the following text for clarity, grammar, and style:\n\n{$content}",
            'generate_tags' => "Generate 5-10 relevant tags for the following text (return only the tags separated by commas):\n\n{$content}",
        };
    }

    private function streamOpenAIResponse(string $prompt): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => config('openai.model'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'stream' => true,
        ]);

        if ($response->successful()) {
            $body = $response->getBody();
            while (!$body->eof()) {
                $line = $body->read(1024);
                echo $line;
                ob_flush();
                flush();
            }
        }
    }
}
