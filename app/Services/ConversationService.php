<?php

namespace App\Services;

use Illuminate\Support\Str;

// {userId}/
// ├── index.json           :  lightweight metadata for all conversations (no messages)
// ├── {conversationId}.json:  individual conversation files with full content

class ConversationService
{
    private function getUserDirectory(int $userId): string
    {
        $dir = storage_path("app/conversations/{$userId}");

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function getIndexPath(int $userId): string
    {
        return $this->getUserDirectory($userId) . '/index.json';
    }

    private function getConversationPath(int $userId, string $conversationId): string
    {
        return $this->getUserDirectory($userId) . "/{$conversationId}.json";
    }

    // get all conversations (metadata only, no messages)
    public function all(int $userId): array
    {
        return $this->loadIndex($userId);
    }

    // find a specific conversation with full content
    public function find(int $userId, string $id): ?array
    {
        $path = $this->getConversationPath($userId, $id);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    // create a new conversation
    public function create(int $userId, string $model): array
    {
        $id = Str::uuid()->toString();
        $now = now()->toISOString();

        $conversation = [
            'id' => $id,
            'title' => 'New conversation',
            'model' => $model,
            'messages' => [],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // save full conversation to individual file
        $this->saveConversation($userId, $id, $conversation);

        // update index with metadata (no messages)
        $this->updateIndex($userId, $id, $conversation);

        return $conversation;
    }

    // update conversation metadata
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

        $this->saveConversation($userId, $id, $conversation);
        $this->updateIndex($userId, $id, $conversation);

        return $conversation;
    }

    // add a message to a conversation
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

        $this->saveConversation($userId, $id, $conversation);
        $this->updateIndex($userId, $id, $conversation);

        return $conversation;
    }

    // delete a conversation
    public function delete(int $userId, string $id): bool
    {
        $conversationPath = $this->getConversationPath($userId, $id);

        if (!file_exists($conversationPath)) {
            return false;
        }

        unlink($conversationPath);

        // remove from index
        $index = $this->loadIndex($userId);
        unset($index[$id]);
        $this->saveIndex($userId, $index);

        return true;
    }

    private function loadIndex(int $userId): array
    {
        $path = $this->getIndexPath($userId);

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    private function saveIndex(int $userId, array $index): void
    {
        $path = $this->getIndexPath($userId);
        file_put_contents($path, json_encode($index, JSON_PRETTY_PRINT));
    }

    // update a single entry in the index (metadata only, no messages)
    private function updateIndex(int $userId, string $id, array $conversation): void
    {
        $index = $this->loadIndex($userId);

        $index[$id] = [
            'id' => $conversation['id'],
            'title' => $conversation['title'],
            'model' => $conversation['model'],
            'created_at' => $conversation['created_at'],
            'updated_at' => $conversation['updated_at'],
        ];

        $this->saveIndex($userId, $index);
    }

    private function saveConversation(int $userId, string $id, array $conversation): void
    {
        $path = $this->getConversationPath($userId, $id);
        file_put_contents($path, json_encode($conversation, JSON_PRETTY_PRINT));
    }
}
