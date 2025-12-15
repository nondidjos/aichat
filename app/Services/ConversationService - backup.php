<?php

namespace App\Services;

use Illuminate\Support\Str;

class ConversationService
{
    private function getUserDirectory(int $userId): string
    {
        $dir = storage_path("app/conversations/{$userId}");

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return "{$dir}/{$userId}.json";
    }

    public function all(int $userId): array
    {
        return $this->load($userId);
    }

    public function find(int $userId, string $id): ?array
    {
        $conversations = $this->load($userId);
        return $conversations[$id] ?? null;
    }

    public function create(int $userId, string $model): array
    {
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
        $this->save($userId, $conversations);

        return $conversation;
    }

    public function update(int $userId, string $id, array $data): ?array
    {
        $conversation = $this->find($userId, $id);

        if (!$conversation) {
            return null;
        }

        // merge new data, always refresh updated_at
        $conversation = array_merge($conversation, $data, [
            'updated_at' => now()->toISOString(),
        ]);

        $this->save($userId, $conversations);

        return $conversations[$id];
    }

    public function addMessage(int $userId, string $id, string $role, string $content): ?array
    {
        $conversation = $this->find($userId, $id);

        if (!$conversation) {
            return null;
        }

        $conversation['messages'][] = [
            'role' => $role,
            'content' => $content,
        ];

        // auto-title from first user message
        if ($conversation['title'] === 'New conversation' && $role === 'user') {
            $conversation['title'] = Str::limit($content, 40);
        }

        $conversation['updated_at'] = now()->toISOString();

        $this->save($userId, $conversations);

        return $conversations[$id];
    }

    public function delete(int $userId, string $id): bool
    {
        $path = $this->getConversationPath($userId, $id);

        if (!isset($conversations[$id])) {
            return false;
        }

        unset($conversations[$id]);
        $this->save($userId, $conversations);

        return true;
    }

    private function loadConversation(string $path): ?array
    {
        $path = $this->getStoragePath($userId);
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        return json_decode($content, true) ?? [];
    }

    private function saveConversation(int $userId, array $conversation): void
    {
        $path = $this->getStoragePath($userId);
        file_put_contents($path, json_encode($conversations, JSON_PRETTY_PRINT));
    }
}
