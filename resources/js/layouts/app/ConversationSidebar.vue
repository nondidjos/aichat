<script setup>

import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui';
import { X } from 'lucide-vue-next';

// sidebar open state (exposed for parent control)
const isOpen = defineModel('open', { type: Boolean, default: false });

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

    <!-- mobile overlay backdrop -->
    <div
        v-if="isOpen"
        class="fixed inset-0 z-40 bg-black/50 md:hidden"
        @click="isOpen = false"
    />

    <!-- conversation list sidebar -->
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r bg-background transition-transform duration-200 md:static md:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <header class="flex items-center gap-2 border-b p-4">
            <Button @click="openNewChat" class="flex-1">New Chat</Button>
            <Button variant="ghost" size="icon" class="md:hidden" @click="isOpen = false">
                <X class="h-4 w-4" />
            </Button>
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
    </aside>
</template>
