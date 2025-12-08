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
        $conversations = $this->conversationService->all();
        $current = $conversationId ? $this->conversationService->find($conversationId) : null;

        return Inertia::render('ask/index', [
            'models' => $this->askService->getModels(),
            'selectedModel' => $current['model'] ?? $this->askService::DEFAULT_MODEL,
            'messages' => $current['messages'] ?? [],
            'conversations' => array_values($conversations),
            'currentConversationId' => $conversationId,
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
        $conversationId = $request->conversationId;

        if (!$conversationId) {
            $conversation = $this->conversationService->create($request->model);
            $conversationId = $conversation['id'];
        }

        // add user message to conversation
        $this->conversationService->addMessage($conversationId, 'user', $request->message);
        $conversation = $this->conversationService->find($conversationId);

        try {
            $response = $this->askService->sendMessage(
                messages: $conversation['messages'],
                model: $request->model
            );

            // add assistant response
            $this->conversationService->addMessage($conversationId, 'assistant', $response);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        // update model if changed
        if ($conversation['model'] !== $request->model) {
            $this->conversationService->update($conversationId, ['model' => $request->model]);
        }

        return redirect()->route('ask.show', $conversationId)->with('error', $error);
    }

    public function destroy(string $conversationId)
    {
        $this->conversationService->delete($conversationId);
        return redirect()->route('ask');
    }

    // create a new conversation with selected model
    public function create(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
        ]);

        $conversation = $this->conversationService->create($request->model);

        return redirect()->route('ask.show', $conversation['id']);
    }
}
