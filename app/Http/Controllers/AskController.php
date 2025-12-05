<?php

namespace App\Http\Controllers;

use App\Services\AskService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AskController extends Controller
{
    public function __construct(private AskService $askService) {}

    public function index()
    {
        return Inertia::render('ask/index', [
            'models' => $this->askService->getModels(),
            'selectedModel' => $this->askService::DEFAULT_MODEL,
            'messages' => [],
        ]);
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model'   => 'required|string',
            'messages' => 'array',
        ]);

        $error = null;
        $history = $request->messages ?? [];

        // add user message to history
        $history[] = [
            'role' => 'user',
            'content' => $request->message,
        ];

        try {
            $response = $this->askService->sendMessage(
                messages: $history,
                model: $request->model
            );

            // add assistant response to history
            $history[] = [
                'role' => 'assistant',
                'content' => $response,
            ];
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return Inertia::render('ask/index', [
            'models'        => $this->askService->getModels(),
            'selectedModel' => $request->model,
            'messages'      => $history,
            'error'         => $error,
        ]);
    }
}
