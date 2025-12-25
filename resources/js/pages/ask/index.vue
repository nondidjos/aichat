<script setup>
import {
    ref,
    computed,
    watch,
    nextTick,
    onMounted
} from 'vue';

import {
    router,
    usePage
} from '@inertiajs/vue3';

import { useStream } from '@laravel/stream-vue';

import {
    Button,
    Avatar,
    AvatarFallback,
    Badge
} from '@/components/ui';

import { Menu, Square } from 'lucide-vue-next';

import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

// custom conversation sidebar layout and header component
import ConversationSidebar from '@/layouts/app/ConversationSidebar.vue';
import PageHeader from '@/components/custom/PageHeader.vue';

const md = new MarkdownIt({
    breaks: true,
    highlight: (str, lang) => lang && hljs.getLanguage(lang) ? hljs.highlight(str, { language: lang }).value : '',
});

const props = defineProps({
    selectedModel: String,
    messages: Array,
    error: String,
    currentConversationId: String,
});

// get user from global inertia props
const page = usePage();
const user = computed(() => page.props.auth?.user);
const userInitials = computed(() => (user.value?.name ?? '').trim().slice(0, 2).toUpperCase());

// form state
const message = ref('');
const model = ref(props.selectedModel);
const conversationId = ref(props.currentConversationId);

// local messages for optimistic UI
const localMessages = ref([...props.messages]);
watch(() => props.messages, (newMessages) => {
    localMessages.value = [...newMessages];
});

// update form when switching conversations
watch(() => props.currentConversationId, (id) => {
    conversationId.value = id;
    model.value = props.selectedModel;
}, { immediate: true });

// streaming hook
const { data: streamData, isStreaming, send, cancel, reset } = useStream('/ask/stream', {
    onFinish: async () => {
        // save the completed response to the conversation
        if (streamData.value && conversationId.value) {
            // extract clean content (without reasoning markers)
            const cleanContent = streamData.value
                .replace(/\[REASONING\][\s\S]*?\[\/REASONING\]/g, '')
                .trim();

            // add to local messages immediately so it doesn't disappear
            localMessages.value.push({ role: 'assistant', content: cleanContent });

            await fetch('/ask/save-response', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    conversationId: conversationId.value,
                    content: streamData.value,
                }),
            });

            // clear stream data after adding to localMessages
            reset();

            // refresh conversations list in sidebar
            router.reload({ only: ['conversations'] });
        }
    },
    onError: (err) => {
        console.error('Streaming error:', err);
    },
});

// extract content without reasoning markers for display
const streamedContent = computed(() => {
    if (!streamData.value) return '';
    return streamData.value
        .replace(/\[REASONING\][\s\S]*?\[\/REASONING\]/g, '')
        .trim();
});

// auto-scroll to bottom on new message
const messagesContainer = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

// scroll to bottom on mount and when conversation changes
onMounted(scrollToBottom);
watch(() => props.currentConversationId, scrollToBottom);

// scroll once when streaming starts (not when message finishes)
watch(isStreaming, (streaming, wasStreaming) => {
    if (streaming && !wasStreaming) {
        scrollToBottom();
    }
});

// mobile sidebar state
const sidebarOpen = ref(false);

// send user message and start streaming
const sendMessage = async () => {
    const userMessage = message.value;
    if (!userMessage.trim() || isStreaming.value) return;

    message.value = '';

    // add user message optimistically
    localMessages.value.push({ role: 'user', content: userMessage });

    // scroll to show user message
    scrollToBottom();

    try {
        // first, send the message to create/update conversation
        const response = await fetch('/ask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                message: userMessage,
                model: model.value,
                conversationId: conversationId.value,
            }),
        });

        const data = await response.json();
        conversationId.value = data.conversationId;

        // update URL if this is a new conversation
        if (!props.currentConversationId) {
            window.history.replaceState({}, '', `/ask/${data.conversationId}`);
        }

        // start streaming the AI response
        send({
            conversationId: data.conversationId,
            model: model.value,
        });
    } catch (err) {
        console.error('Error sending message:', err);
    }
};

// cancel streaming
const stopStreaming = () => {
    cancel();
};
</script>


<template>
    <main class="bg-background text-foreground flex h-screen">
        <ConversationSidebar
            v-model:open="sidebarOpen"
            :current-conversation-id="currentConversationId"
            :selected-model="selectedModel"
        />

        <!-- main chat area -->
        <section class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <!-- mobile header with menu toggle -->
            <div class="flex items-center border-b md:hidden">
                <Button variant="ghost" size="icon" class="m-2" @click="sidebarOpen = true">
                    <Menu class="h-5 w-5" />
                </Button>
                <span class="text-sm text-muted-foreground font-medium">{{selectedModel}}</span>
            </div>
            <div class="hidden md:block">
                <PageHeader :selected-model="selectedModel" />
            </div>

            <!-- messages -->
            <section ref="messagesContainer" class="flex-1 space-y-4 overflow-y-auto px-4 py-4 sm:px-8 md:px-16 lg:px-32 xl:px-48">
                <!-- empty state -->
                <p v-if="!localMessages.length && !isStreaming" class="flex h-full items-center justify-center text-muted-foreground">Start a conversation below.</p>

                <!-- individual message bubble -->
                <article
                    v-for="(msg, index) in localMessages"
                    :key="index"
                    class="flex gap-3 py-2"
                    :class="msg.role === 'user' ? 'flex-row-reverse' : ''"
                >
                    <Avatar class="h-8 w-8 shrink-0">
                        <AvatarFallback :class="msg.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'">
                            {{ msg.role === 'user' ? userInitials : 'AI' }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- user or ai? -->
                    <div class="min-w-0 flex-1 space-y-1">
                        <!-- name styling -->
                        <div class="flex items-center gap-2" :class="msg.role === 'user' ? 'justify-end' : ''">
                            <span class="text-sm font-medium">{{ msg.role === 'user' ? 'You' : 'Assistant' }}</span>
                            <Badge v-if="msg.role === 'assistant'" variant="secondary" class="text-xs">AI</Badge>
                        </div>

                        <!-- message styling -->
                        <div v-if="msg.role === 'assistant'" class="prose prose-sm max-w-none overflow-x-auto break-words dark:prose-invert [&_pre]:overflow-x-auto" v-html="md.render(msg.content)" />
                        <div v-else class="mt-1 rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2">
                            <p class="whitespace-pre-wrap text-sm">{{ msg.content }}</p>
                        </div>
                    </div>
                </article>

                <!-- streaming response -->
                <article v-if="isStreaming" class="flex gap-3 py-2">
                    <Avatar class="h-8 w-8 shrink-0">
                        <AvatarFallback class="bg-muted">AI</AvatarFallback>
                    </Avatar>

                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">Assistant</span>
                            <Badge variant="secondary" class="text-xs">AI</Badge>
                            <span class="text-xs text-muted-foreground animate-pulse">Streaming...</span>
                        </div>

                        <div class="prose prose-sm max-w-none overflow-x-auto break-words dark:prose-invert [&_pre]:overflow-x-auto" v-html="md.render(streamedContent || '...')" />
                    </div>
                </article>
            </section>

            <!-- message input form -->
            <footer class="border-t px-4 py-3 sm:px-6 sm:py-4">
                <form @submit.prevent="sendMessage" class="flex gap-3">
                    <textarea
                        v-model="message"
                        rows="2"
                        @keydown.enter.exact.prevent="sendMessage"
                        :disabled="isStreaming"
                        class="flex-1 resize-none rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
                        placeholder="Type your message..."
                    />
                    <Button
                        v-if="isStreaming"
                        type="button"
                        variant="destructive"
                        @click="stopStreaming"
                        class="h-auto shrink-0"
                    >
                        <Square class="h-4 w-4 mr-1" />
                        Stop
                    </Button>
                    <Button
                        v-else
                        type="submit"
                        :disabled="!message.trim()"
                        class="h-auto shrink-0"
                    >
                        Send
                    </Button>
                </form>
            </footer>
        </section>
    </main>
</template>
