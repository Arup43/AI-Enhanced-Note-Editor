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

        $apiKey = config('openai.api_key');
        $model = config('openai.model');

        if (empty($apiKey)) {
            return response()->json(['error' => 'OpenAI API key not configured'], 500);
        }

        return new StreamedResponse(function () use ($apiKey, $model, $prompt) {
            $this->streamOpenAIResponse($apiKey, $model, $prompt);
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    private function streamOpenAIResponse(string $apiKey, string $model, string $prompt): void
    {
        $postData = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'stream' => true,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($postData),
                ],
                'content' => $postData,
            ],
        ]);

        $stream = fopen('https://api.openai.com/v1/chat/completions', 'r', false, $context);

        if (!$stream) {
            echo "data: " . json_encode(['error' => 'Failed to connect to OpenAI']) . "\n\n";
            return;
        }

        while (!feof($stream)) {
            $line = fgets($stream);

            if ($line === false) {
                break;
            }

            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (strpos($line, 'data: ') === 0) {
                $data = substr($line, 6);

                if ($data === '[DONE]') {
                    echo "data: [DONE]\n\n";
                    break;
                }

                try {
                    $json = json_decode($data, true);

                    if (isset($json['choices'][0]['delta']['content'])) {
                        $content = $json['choices'][0]['delta']['content'];
                        echo "data: " . json_encode(['content' => $content]) . "\n\n";

                        // Flush output immediately
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }
                } catch (\Exception $e) {
                    // Skip malformed JSON
                    continue;
                }
            }
        }

        fclose($stream);
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
