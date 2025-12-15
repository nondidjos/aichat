<script setup>
import {
    ref,
    computed,
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
    breaks: true,
    highlight: (str, lang) => lang && hljs.getLanguage(lang) ? hljs.highlight(str, { language: lang }).value : '',
});

const props = defineProps({
    models: Array,
    selectedModel: String,
    messages: Array,
    error: String,
    conversations: Array,
    currentConversationId: String,
    user: Object,
});

// extract first two letters of user's name for avatar
const userInitials = computed(() => (props.user?.name ?? '').trim().slice(0, 2).toUpperCase());

// message state
const form = useForm({
    message: '',
    model: props.selectedModel,
    conversationId: props.currentConversationId,
});

// local messages for optimistic UI
const localMessages = ref([...props.messages]);
watch(() => props.messages, (newMessages) => {
    localMessages.value = [...newMessages];
});

// update form when switching conversations
watch(() => props.currentConversationId, (id) => {
    form.conversationId = id;
    form.model = props.selectedModel;
}, { immediate: true });

// auto-scroll to bottom on new messages
const messagesContainer = ref(null);
watch(() => localMessages.value.length, () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
});

// user dropdown
const showUserMenu = ref(false);

// model picker dialog
const showModelPicker = ref(false);
const pickedModel = ref(props.selectedModel);

// show model picker if no conversation selected
onMounted(() => {
    if (!props.currentConversationId) {
        showModelPicker.value = true;
    }
});

// open model picker for starting a new conversation
const openNewChat = () => {
    pickedModel.value = props.selectedModel;
    showModelPicker.value = true;
};

// create new conversation with selected model
const confirmModel = () => {
    showModelPicker.value = false;
    router.post('/ask/new', { model: pickedModel.value });
};

// send user message and add optimistically to ui
const sendMessage = () => {
    const message = form.message;
    if (!message.trim()) return;

    form.message = '';
    localMessages.value.push({ role: 'user', content: message });

    router.post('/ask', {
        message,
        model: form.model,
        conversationId: props.currentConversationId
    });
};
</script>


<template>
    <main class="bg-background text-foreground flex h-screen">
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

        <!-- conversation list -->
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
        </aside>

        <!-- main chat area -->
        <section class="flex flex-1 flex-col">
            <header class="flex items-center justify-between border-b px-6 py-3">
                <span class="text-sm text-muted-foreground">{{ selectedModel }}</span>

                <!-- user dropdown menu -->
                <div class="relative">
                    <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-2 rounded px-2 py-1 hover:bg-muted">
                        <Avatar class="h-6 w-6">
                            <AvatarFallback class="text-xs">{{ userInitials }}</AvatarFallback>
                        </Avatar>
                        <span class="text-sm">{{ user.name }}</span>
                    </button>

                    <div v-if="showUserMenu" @click.self="showUserMenu = false" class="fixed inset-0 z-40" />
                    <!-- dropdown content -->
                    <div
                        v-if="showUserMenu"
                        class="absolute right-0 z-50 mt-2 w-48 rounded-lg border bg-background shadow-lg"
                    >
                        <Link href="/settings" class="block px-4 py-2 text-sm hover:bg-muted">Settings</Link>
                        <form @submit.prevent="router.post('/logout')" class="block">
                            <button type="submit" class="w-full cursor-pointer px-4 py-2 text-left text-sm text-destructive hover:bg-muted">Sign out</button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- messages -->
            <section ref="messagesContainer" class="flex-1 space-y-4 overflow-y-auto px-48 py-4">
                <!-- empty state -->
                <p v-if="!localMessages.length" class="flex h-full items-center justify-center text-muted-foreground">Start a conversation below.</p>

                <!-- individual message bubble -->
                <article
                    v-for="(message, index) in localMessages"
                    :key="index"
                    class="flex gap-3"
                >
                    <Avatar class="h-8 w-8 shrink-0">
                        <AvatarFallback :class="message.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'">
                            {{ message.role === 'user' ? userInitials : 'AI' }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ message.role === 'user' ? 'You' : 'Assistant' }}</span>
                            <Badge v-if="message.role === 'assistant'" variant="secondary" class="text-xs">AI</Badge>
                        </div>

                        <div v-if="message.role === 'assistant'" class="prose prose-sm max-w-none dark:prose-invert" v-html="md.render(message.content)" />
                        <p v-else class="mt-1 whitespace-pre-wrap text-sm">{{ message.content }}</p>
                    </div>
                </article>
            </section>

            <!-- message input form -->
            <footer class="border-t px-6 py-4">
                <form @submit.prevent="sendMessage" class="flex gap-3">
                    <textarea
                        v-model="form.message"
                        rows="2"
                        @keydown.enter.exact.prevent="sendMessage"
                        class="flex-1 resize-none rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="Type your message..."
                    />
                    <Button type="submit" :disabled="form.processing || !form.message.trim()" class="self-end">
                        {{ form.processing ? 'Sending...' : 'Send' }}
                    </Button>
                </form>
            </footer>
        </section>
    </main>
</template>
