<script setup>

import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui';
import { Settings } from 'lucide-vue-next';

const page = usePage();

// get shared data from Inertia
const conversations = computed(() => page.props.conversations ?? []);
const models = computed(() => page.props.models ?? []);

const props = defineProps({
    currentConversationId: {
        type: String,
        default: null,
    },
    selectedModel: {
        type: String,
        default: null,
    },
});

// model picker dialog
const showModelPicker = ref(false);
const pickedModel = ref(props.selectedModel || models.value[0]?.id);

// open model picker for starting a new conversation
const openNewChat = () => {
    pickedModel.value = props.selectedModel || models.value[0]?.id;
    showModelPicker.value = true;
};

// create new conversation with selected model
const confirmModel = () => {
    showModelPicker.value = false;
    router.post('/ask/new', { model: pickedModel.value });
};
</script>

<template>
    <!-- model picker modal -->
    <div v-if="showModelPicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-80 rounded-lg border bg-background p-6">
            <h2 class="mb-2 text-lg font-medium">Pick a model</h2>
            <select v-model="pickedModel" class="mb-4 h-10 w-full rounded border border-input bg-background px-3 text-sm text-foreground">
                <option v-for="model in models" :key="model.id" :value="model.id">{{ model.name }}</option>
            </select>
            <div class="flex justify-end gap-2">
                <button v-if="conversations.length" @click="showModelPicker = false" class="px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground">Cancel</button>
                <Button @click="confirmModel">Start</Button>
            </div>
        </div>
    </div>

    <!-- conversation list sidebar -->
    <aside class="flex w-64 flex-col border-r">
        <header class="border-b p-4">
            <Button @click="openNewChat" class="w-full">New Chat</Button>
        </header>
        <nav class="flex-1 overflow-y-auto">
            <Link
                v-for="conversation in conversations"
                :key="conversation.id"
                :href="`/ask/${conversation.id}`"
                class="group flex justify-between px-4 py-3 text-sm hover:bg-muted"
                :class="{ 'bg-muted': conversation.id === currentConversationId }"
            >
                <span class="truncate">{{ conversation.title }}</span>
                <button
                    @click.prevent="router.delete(`/ask/${conversation.id}`)"
                    class="cursor-pointer text-muted-foreground opacity-0 transition group-hover:opacity-100 hover:text-destructive"
                >
                    ✕
                </button>
            </Link>
        </nav>
        <footer class="border-t p-4">
            <Link href="/settings">
                <Button variant="ghost" class="w-full justify-start">
                    <Settings class="mr-2 h-4 w-4" />
                    Settings
                </Button>
            </Link>
        </footer>
    </aside>
</template>
