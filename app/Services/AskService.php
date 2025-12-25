<?php

declare(strict_types=1);

namespace App\Services;

use Generator;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;

class AskService
{
    public const DEFAULT_MODEL = 'openai/gpt-4o-mini';

    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->baseUrl = rtrim(config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
    }

    /**
     * Récupère la liste des modèles disponibles.
     *
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     description: string,
     *     context_length: int,
     *     max_completion_tokens: int,
     *     input_modalities: array<string>,
     *     output_modalities: array<string>,
     *     supported_parameters: array<string>
     * }>
     */
    public function getModels(): array
    {
        return cache()->remember('openrouter.models', now()->addHour(), function (): array {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json('data', []))
                ->sortBy('name')
                ->map(fn (array $model): array => [
                    'id' => $model['id'],
                    'name' => $model['name'],
                    'description' => $model['description'] ?? '',
                    'context_length' => $model['context_length'] ?? 0,
                    'max_completion_tokens' => $model['top_provider']['max_completion_tokens'] ?? 0,
                    'input_modalities' => $model['architecture']['input_modalities'] ?? [],
                    'output_modalities' => $model['architecture']['output_modalities'] ?? [],
                    'supported_parameters' => $model['supported_parameters'] ?? [],
                ])
                ->values()
                ->toArray()
            ;
        });
    }

    /**
     * Envoie un message et retourne la réponse du modèle.
     *
     * @param array<int, array{
     *     role: 'assistant'|'system'|'tool'|'user',
     *     content: array<int, array{
     *         type: 'image_url'|'text',
     *         text?: string,
     *         image_url?: array{url: string, detail?: string}
     *     }>|string
     * }> $messages
     */
    public function sendMessage(array $messages, ?string $model = null, float $temperature = 1.0): string
    {
        $model = $model ?? self::DEFAULT_MODEL;
        $messages = [$this->getSystemPrompt(), ...$messages];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])
            ->timeout(120)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ])
        ;

        // errors
        if ($response->failed()) {
            $error = $response->json('error.message', 'Erreur inconnue');
            throw new \RuntimeException("Erreur API: {$error}");
        }

        return $response->json('choices.0.message.content', '');
    }

    /**
     * Stream a message response in real-time to the output.
     * Compatible with Laravel's response()->stream() and useStream on frontend.
     *
     * @param array $messages The conversation messages
     * @param string|null $model The model ID to use
     * @param float $temperature The temperature setting
     */
    public function streamToOutput(array $messages, ?string $model = null, float $temperature = 1.0): void
    {
        $response = $this->sendStreamRequest($messages, $model, $temperature);

        if ($response->failed()) {
            echo '[ERROR] ' . $response->json('error.message', 'HTTP Error');
            $this->flush();
            return;
        }

        foreach ($this->parseSSEStream($response->toPsrResponse()->getBody()) as $event) {
            if ($event['type'] === 'error') {
                echo '[ERROR] ' . $event['data'];
                $this->flush();
                return;
            }

            if ($event['type'] === 'content' && $event['data']) {
                echo $event['data'];
                $this->flush();
            }

            // for reasoning models, wrap in markers
            if ($event['type'] === 'reasoning' && $event['data']) {
                echo '[REASONING]' . $event['data'] . '[/REASONING]';
                $this->flush();
            }
        }
    }

    /**
     * Flush the output buffer immediately.
     */
    private function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * Send a streaming request to the API.
     */
    private function sendStreamRequest(array $messages, ?string $model, float $temperature): \Illuminate\Http\Client\Response
    {
        $model = $model ?? self::DEFAULT_MODEL;
        $messages = [$this->getSystemPrompt(), ...$messages];

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'stream' => true,
            ]);
    }

    /**
     * Parse an SSE stream and yield events.
     *
     * @return Generator<array{type: string, data: string|null}>
     */
    private function parseSSEStream(StreamInterface $body): Generator
    {
        $buffer = '';

        while (!$body->eof()) {
            $buffer .= $body->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($event = $this->parseSSELine($line)) {
                    yield $event;
                }
            }
        }
    }

    /**
     * Parse a single SSE line.
     */
    private function parseSSELine(string $line): ?array
    {
        if ($line === '' || str_starts_with($line, ':')) {
            return null;
        }

        if (!str_starts_with($line, 'data: ')) {
            return null;
        }

        $data = substr($line, 6);

        if ($data === '[DONE]') {
            return ['type' => 'done', 'data' => null];
        }

        return $this->parseJSON($data);
    }

    /**
     * Parse the JSON from an SSE chunk.
     */
    private function parseJSON(string $json): ?array
    {
        try {
            $parsed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (isset($parsed['error'])) {
                return ['type' => 'error', 'data' => $parsed['error']['message'] ?? 'Unknown error'];
            }

            $delta = $parsed['choices'][0]['delta'] ?? [];

            if (!empty($delta['content'])) {
                return ['type' => 'content', 'data' => $delta['content']];
            }

            if (!empty($delta['reasoning'])) {
                return ['type' => 'reasoning', 'data' => $delta['reasoning']];
            }

            if (!empty($delta['reasoning_content'])) {
                return ['type' => 'reasoning', 'data' => $delta['reasoning_content']];
            }

            return null;
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * Retourne le prompt système.
     *
     * @return array{role: 'system', content: string}
     */
    private function getSystemPrompt(): array
    {
        $user = auth()->user()?->name ?? 'l\'utilisateur';
        $now = now()->locale('fr')->format('l d F Y H:i');

        return [
            'role' => 'system',
            'content' => view('prompts.system', [
                'now' => $now,
                'user' => $user,
            ])->render(),
        ];
    }
}
