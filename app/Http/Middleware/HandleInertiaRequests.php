<?php

namespace App\Http\Middleware;

use App\Services\AskService;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // shared conversation data for sidebar
            'conversations' => fn () => $request->user()
                ? array_values(app(ConversationService::class)->all($request->user()->id))
                : [],
            'models' => fn () => app(AskService::class)->getModels(),
        ];
    }
}
