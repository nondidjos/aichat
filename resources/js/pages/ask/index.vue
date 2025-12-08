<script setup>
import {
    ref,
    onMounted,
    watch,
    nextTick
} from 'vue';

import {
    useForm,
    router,
    Link
} from '@inertiajs/vue3';

import {
    Button,
    Alert,
    AlertTitle,
    AlertDescription,
    Avatar,
    AvatarFallback,
    Badge
} from '@/components/ui';

import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';


const md = new MarkdownIt({
    highlight: (str, lang) => lang && hljs.getLanguage(lang) ? hljs.highlight(str, { language: lang }).value : '',
});

const props = defineProps({
    models: Array,
    selectedModel: String,
    messages: Array,
    error: String,
    conversations: Array,
    currentConversationId: String,
});

// message state
const form = useForm({
    message: '',
    model: props.selectedModel,
    conversationId: props.currentConversationId,
});

// update form when switching conversations
watch(() => props.currentConversationId, (id) => {
    form.conversationId = id;
    form.model = props.selectedModel;
});

// auto-scroll to bottom on new messages
const messagesContainer = ref(null);
watch(() => props.messages.length, () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
});

// model picker dialog
const showModelPicker = ref(false);
const pickedModel = ref(props.selectedModel);

// show model picker if no conversations exist
onMounted(() => {
    if (!props.conversations.length && !props.currentConversationId) {
        showModelPicker.value = true;
    }
});

const openNewChat = () => {
    pickedModel.value = props.selectedModel;
    showModelPicker.value = true;
};

const confirmModel = () => {
    showModelPicker.value = false;
    router.post('/ask/new', { model: pickedModel.value });
};

const sendMessage = () => {
    form.post('/ask', { onSuccess: () => form.message = '' });
};
</script>

<template>
    <main class="bg-background text-foreground flex h-screen">
        <!-- model picker modal -->
        <div v-if="showModelPicker" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-background border rounded-lg p-6 w-80">
                <h2 class="text-lg font-medium mb-2">Pick a model</h2>
                <select v-model="pickedModel" class="border-input bg-background text-foreground h-10 w-full rounded border px-3 text-sm mb-4">
                    <option v-for="model in models" :key="model.id" :value="model.id">{{ model.name }}</option>
                </select>
                <div class="flex gap-2 justify-end">
                    <button v-if="conversations.length" @click="showModelPicker = false" class="px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground">Cancel</button>
                    <Button @click="confirmModel">Start</Button>
                </div>
            </div>
        </div>

        <!-- sidebar -->
        <aside class="w-64 border-r flex flex-col">
            <header class="p-4 border-b">
                <Button @click="openNewChat" class="w-full">New Chat</Button>
            </header>
            <nav class="flex-1 overflow-y-auto">
                <Link v-for="conversation in conversations" :key="conversation.id" :href="`/ask/${conversation.id}`"
                    class="group flex justify-between px-4 py-3 hover:bg-muted text-sm"
                    :class="{ 'bg-muted': conversation.id === currentConversationId }">
                    <span class="truncate">{{ conversation.title }}</span>
                    <button @click.prevent="router.delete(`/ask/${conversation.id}`)" class="opacity-0 group-hover:opacity-100 text-muted-foreground hover:text-destructive">✕</button>
                </Link>
            </nav>
        </aside>

        <!-- chat -->
        <article class="flex-1 flex flex-col">
            <header class="border-b px-6 py-3 text-sm text-muted-foreground">{{ selectedModel }}</header>

            <Alert v-if="error" variant="destructive" class="m-4">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <section ref="messagesContainer" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                <p v-if="!messages.length" class="flex items-center justify-center h-full text-muted-foreground">Start a conversation below.</p>
                <article v-for="(message, index) in messages" :key="index" class="flex gap-3">
                    <Avatar class="h-8 w-8 shrink-0">
                        <AvatarFallback :class="message.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'">{{ message.role === 'user' ? 'U' : 'AI' }}</AvatarFallback>
                    </Avatar>
                    <div class="flex-1">
                        <span class="font-medium text-sm">{{ message.role === 'user' ? 'You' : 'Assistant' }}</span>
                        <Badge v-if="message.role === 'assistant'" variant="secondary" class="text-xs ml-2">AI</Badge>
                        <div v-if="message.role === 'assistant'" class="prose prose-sm dark:prose-invert max-w-none mt-1" v-html="md.render(message.content)" />
                        <p v-else class="text-sm whitespace-pre-wrap mt-1">{{ message.content }}</p>
                    </div>
                </article>
            </section>

            <footer class="border-t px-6 py-4">
                <form @submit.prevent="sendMessage" class="flex gap-3">
                    <textarea v-model="form.message" rows="2" @keydown.enter.exact.prevent="sendMessage"
                        class="border-input bg-background text-foreground flex-1 rounded-md border px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                        placeholder="Type your message..." />
                    <Button type="submit" :disabled="form.processing || !form.message.trim()" class="self-end">{{ form.processing ? 'Sending...' : 'Send' }}</Button>
                </form>
            </footer>
        </article>
    </main>
</template>
