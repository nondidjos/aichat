<?php

namespace App\Http\Controllers;

use App\Services\AskService;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AskController extends Controller
{
    public function __construct(
        private AskService $askService,
        private ConversationService $conversationService
    ) {}

    public function index(?string $conversationId = null)
    {
        $userId = auth()->id();
        $user = auth()->user();
        $conversations = $this->conversationService->all($userId);
        $current = $conversationId ? $this->conversationService->find($userId, $conversationId) : null;

        return Inertia::render('ask/index', [
            'models' => $this->askService->getModels(),
            'selectedModel' => $current['model'] ?? $this->askService::DEFAULT_MODEL,
            'messages' => $current['messages'] ?? [],
            'conversations' => array_values($conversations),
            'currentConversationId' => $conversationId,
            'user' => $user->only(['id', 'name', 'email']),
            'hasApiKey' => !empty($user->api_key),
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model'   => 'required|string',
            'conversationId' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $conversationId = $request->conversationId;

        // create conversation if needed
        if (!$conversationId) {
            $conversation = $this->conversationService->create($userId, $request->model);
            $conversationId = $conversation['id'];
        }

        // add user message to conversation
        $this->conversationService->addMessage($userId, $conversationId, 'user', $request->message);
        $conversation = $this->conversationService->find($userId, $conversationId);

        // update model if changed
        if ($conversation['model'] !== $request->model) {
            $this->conversationService->update($userId, $conversationId, ['model' => $request->model]);
        }

        // return the conversation ID for the frontend to use for streaming
        return response()->json([
            'conversationId' => $conversationId,
            'messages' => $conversation['messages'],
        ]);
    }

    /**
     * Stream the AI response in real-time using SSE.
     */
    public function stream(Request $request): StreamedResponse
    {
        $request->validate([
            'conversationId' => 'required|string',
            'model' => 'required|string',
        ]);

        $user = auth()->user();
        $userId = $user->id;
        $conversation = $this->conversationService->find($userId, $request->conversationId);

        if (!$conversation) {
            return response()->stream(
                fn() => print('[ERROR] Conversation not found'),
                headers: ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        // Check if user has API key
        if (empty($user->api_key)) {
            return response()->stream(
                fn() => print('[ERROR] No API key configured. Please add your API key in Settings.'),
                headers: ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        // Decrypt user's API key
        try {
            $apiKey = Crypt::decryptString($user->api_key);
        } catch (\Exception $e) {
            return response()->stream(
                fn() => print('[ERROR] Failed to decrypt API key. Please re-enter your API key in Settings.'),
                headers: ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $messages = $conversation['messages'];
        $model = $request->model;

        return response()->stream(
            function () use ($messages, $model, $apiKey): void {
                $this->askService->streamToOutput($messages, $model, $apiKey);
            },
            headers: [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Cache-Control' => 'no-cache, no-store',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    /**
     * Save the completed AI response to the conversation.
     */
    public function saveResponse(Request $request)
    {
        $request->validate([
            'conversationId' => 'required|string',
            'content' => 'required|string',
        ]);

        $userId = auth()->id();

        // remove reasoning markers before saving
        $content = preg_replace('/\[REASONING\][\s\S]*?\[\/REASONING\]/', '', $request->content);
        $content = trim($content);

        $this->conversationService->addMessage($userId, $request->conversationId, 'assistant', $content);

        return response()->json(['success' => true]);
    }

    public function destroy(string $conversationId)
    {
        $this->conversationService->delete(auth()->id(), $conversationId);
        return redirect()->route('ask');
    }

    // create a new conversation with selected model
    public function create(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
        ]);

        $conversation = $this->conversationService->create(auth()->id(), $request->model);

        return redirect()->route('ask.show', $conversation['id']);
    }
}
