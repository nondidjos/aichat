<?php

namespace App\Services;

use Illuminate\Support\Str;

class ConversationService
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = storage_path('app/conversations.json');
    }

    public function all(): array
    {
        return $this->load();
    }

    public function find(string $id): ?array
    {
        $conversations = $this->load();
        return $conversations[$id] ?? null;
    }

    public function create(string $model): array
    {
        $conversations = $this->load();

        $id = Str::uuid()->toString();
        $conversation = [
            'id' => $id,
            'title' => 'New conversation',
            'model' => $model,
            'messages' => [],
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        $conversations[$id] = $conversation;
        $this->save($conversations);

        return $conversation;
    }

    public function update(string $id, array $data): ?array
    {
        $conversations = $this->load();

        if (!isset($conversations[$id])) {
            return null;
        }

        // merge new data, always refresh updated_at
        $conversations[$id] = array_merge($conversations[$id], $data, [
            'updated_at' => now()->toISOString(),
        ]);

        $this->save($conversations);

        return $conversations[$id];
    }

    public function addMessage(string $id, string $role, string $content): ?array
    {
        $conversations = $this->load();

        if (!isset($conversations[$id])) {
            return null;
        }

        $conversations[$id]['messages'][] = [
            'role' => $role,
            'content' => $content,
        ];

        // auto-title from first user message
        if ($conversations[$id]['title'] === 'New conversation' && $role === 'user') {
            $conversations[$id]['title'] = Str::limit($content, 40);
        }

        $conversations[$id]['updated_at'] = now()->toISOString();

        $this->save($conversations);

        return $conversations[$id];
    }

    public function delete(string $id): bool
    {
        $conversations = $this->load();

        if (!isset($conversations[$id])) {
            return false;
        }

        unset($conversations[$id]);
        $this->save($conversations);

        return true;
    }

    private function load(): array
    {
        if (!file_exists($this->storagePath)) {
            return [];
        }

        $content = file_get_contents($this->storagePath);
        return json_decode($content, true) ?? [];
    }

    private function save(array $conversations): void
    {
        file_put_contents($this->storagePath, json_encode($conversations, JSON_PRETTY_PRINT));
    }
}
