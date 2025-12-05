<script setup>
import { useForm } from '@inertiajs/vue3';
import { Button, Label, Card, CardContent, CardHeader, CardTitle, Alert, AlertDescription, AlertTitle } from '@/components/ui';
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const md = new MarkdownIt({
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (__) {}
        }
        return '';
    },
});

const props = defineProps({
    models: Array,
    selectedModel: String,
    messages: Array,
    error: String,
});

const renderMarkdown = (content) => md.render(content);

const form = useForm({
    message: '',
    model: props.selectedModel,
    messages: props.messages,
});

const submit = () => {
    form.post('/ask', {
        onSuccess: () => {
            form.message = '';
            form.messages = props.messages;
        },
    });
};
</script>

<template>
    <div class="bg-background min-h-screen p-8">
        <div class="mx-auto max-w-2xl space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Ask AI</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="model">Model</Label>
                            <select
                                id="model"
                                v-model="form.model"
                                class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                            >
                                <option v-for="model in props.models" :key="model.id" :value="model.id">{{ model.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="message">Message</Label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="4"
                                class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                                placeholder="Type your question..."
                            ></textarea>
                        </div>

                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Sending...' : 'Send' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Alert v-if="props.error" variant="destructive">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ props.error }}</AlertDescription>
            </Alert>

            <div v-if="props.messages.length" class="space-y-4">
                <div v-for="(msg, index) in props.messages" :key="index">
                    <Card :class="msg.role === 'user' ? 'border-blue-500/50' : ''">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm">{{ msg.role === 'user' ? 'You' : 'AI' }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="msg.role === 'assistant'" class="prose dark:prose-invert max-w-none" v-html="renderMarkdown(msg.content)"></div>
                            <p v-else class="whitespace-pre-wrap">{{ msg.content }}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
