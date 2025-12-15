<?php

namespace App\Http\Controllers;

use App\Services\AskService;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AskController extends Controller
{
    public function __construct(
        private AskService $askService,
        private ConversationService $conversationService
    ) {}

    public function index(?string $conversationId = null)
    {
        $userId = auth()->id();
        $conversations = $this->conversationService->all($userId);
        $current = $conversationId ? $this->conversationService->find($userId, $conversationId) : null;

        return Inertia::render('ask/index', [
            'models' => $this->askService->getModels(),
            'selectedModel' => $current['model'] ?? $this->askService::DEFAULT_MODEL,
            'messages' => $current['messages'] ?? [],
            'conversations' => array_values($conversations),
            'currentConversationId' => $conversationId,
            'user' => auth()->user()->only(['id', 'name', 'email']),
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model'   => 'required|string',
            'conversationId' => 'nullable|string',
        ]);

        $error = null;
        $userId = auth()->id();
        $conversationId = $request->conversationId;

        if (!$conversationId) {
            $conversation = $this->conversationService->create($userId, $request->model);
            $conversationId = $conversation['id'];
        }

        // add user message to conversation
        $this->conversationService->addMessage($userId, $conversationId, 'user', $request->message);
        $conversation = $this->conversationService->find($userId, $conversationId);

        try {
            $response = $this->askService->sendMessage(
                messages: $conversation['messages'],
                model: $request->model
            );

            // add assistant response
            $this->conversationService->addMessage($userId, $conversationId, 'assistant', $response);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        // update model if changed
        if ($conversation['model'] !== $request->model) {
            $this->conversationService->update($userId, $conversationId, ['model' => $request->model]);
        }

        return redirect()->route('ask.show', $conversationId)->with('error', $error);
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
