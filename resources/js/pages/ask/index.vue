<script setup>
import {
    ref,
    computed,
    watch,
    nextTick,
    onMounted
} from 'vue';

import {
    useForm,
    router,
    usePage
} from '@inertiajs/vue3';

import {
    Button,
    Avatar,
    AvatarFallback,
    Badge
} from '@/components/ui';

import { Menu } from 'lucide-vue-next';

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

// auto-scroll to bottom on new message
const messagesContainer = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

watch(() => localMessages.value.length, scrollToBottom);

// scroll to bottom on mount and when conversation changes
onMounted(scrollToBottom);
watch(() => props.currentConversationId, scrollToBottom);

// mobile sidebar state
const sidebarOpen = ref(false);

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
                <p v-if="!localMessages.length" class="flex h-full items-center justify-center text-muted-foreground">Start a conversation below.</p>

                <!-- individual message bubble -->
                <article
                    v-for="(message, index) in localMessages"
                    :key="index"
                    class="flex gap-3 py-2"
                    :class="message.role === 'user' ? 'flex-row-reverse' : ''"
                >
                    <Avatar class="h-8 w-8 shrink-0">
                        <AvatarFallback :class="message.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted'">
                            {{ message.role === 'user' ? userInitials : 'AI' }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- user or ai? -->
                    <div class="min-w-0 flex-1 space-y-1">
                        <!-- name styling -->
                        <div class="flex items-center gap-2" :class="message.role === 'user' ? 'justify-end' : ''">
                            <span class="text-sm font-medium">{{ message.role === 'user' ? 'You' : 'Assistant' }}</span>
                            <Badge v-if="message.role === 'assistant'" variant="secondary" class="text-xs">AI</Badge>
                        </div>

                        <!-- message styling -->
                        <div v-if="message.role === 'assistant'" class="prose prose-sm max-w-none overflow-x-auto break-words dark:prose-invert [&_pre]:overflow-x-auto" v-html="md.render(message.content)" />
                        <div v-else class="mt-1 rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2">
                            <p class="whitespace-pre-wrap text-sm">{{ message.content }}</p>
                        </div>
                    </div>
                </article>
            </section>

            <!-- message input form -->
            <footer class="border-t px-4 py-3 sm:px-6 sm:py-4">
                <form @submit.prevent="sendMessage" class="flex gap-3">
                    <textarea
                        v-model="form.message"
                        rows="2"
                        @keydown.enter.exact.prevent="sendMessage"
                        class="flex-1 resize-none rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        placeholder="Type your message..."
                    />
                    <Button type="submit" :disabled="form.processing || !form.message.trim()" class="h-auto">
                        {{ form.processing ? 'Sending...' : 'Send' }}
                    </Button>
                </form>
            </footer>
        </section>
    </main>
</template>
