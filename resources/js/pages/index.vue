<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { Bot, Sparkles, MessageSquare, Zap, ArrowRight, BrainCircuit, Code, Lightbulb } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const agents = [
    {
        name: 'Assistant',
        description: 'General-purpose AI for everyday tasks',
        icon: Bot,
        iconColor: 'text-blue-500',
        bg: 'bg-blue-500/10',
    },
    {
        name: 'Coder',
        description: 'Expert in programming and debugging',
        icon: Code,
        iconColor: 'text-emerald-500',
        bg: 'bg-emerald-500/10',
    },
    {
        name: 'Creative',
        description: 'Imaginative ideas and content creation',
        icon: Lightbulb,
        iconColor: 'text-amber-500',
        bg: 'bg-amber-500/10',
    },
    {
        name: 'Analyst',
        description: 'Data insights and logical reasoning',
        icon: BrainCircuit,
        iconColor: 'text-violet-500',
        bg: 'bg-violet-500/10',
    },
];

const features = [
    {
        icon: Bot,
        title: 'Choose Your Agent',
        description: 'Select from specialized AI agents tailored to your specific needs.',
    },
    {
        icon: MessageSquare,
        title: 'Natural Conversations',
        description: 'Engage in fluid, context-aware dialogues that understand your intent.',
    },
    {
        icon: Zap,
        title: 'Instant Responses',
        description: 'Get fast, accurate answers powered by cutting-edge AI models.',
    },
];
</script>

<template>
    <Head>
        <title>AI Chat - Select Your Agent | Personalized AI Conversations</title>
        <meta name="description" content="Choose from specialized AI agents for coding, creative writing, data analysis, and more. Get personalized AI assistance that understands your unique needs." />
        <meta name="keywords" content="AI chat, AI assistant, chatbot, artificial intelligence, coding assistant, creative AI, data analysis" />
        <meta name="robots" content="index, follow" />
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:title" content="AI Chat - Select Your Agent" />
        <meta property="og:description" content="Choose from specialized AI agents for coding, creative writing, data analysis, and more. Get personalized AI assistance." />
        <meta property="og:image" content="/og-image.png" />
        
        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="AI Chat - Select Your Agent" />
        <meta name="twitter:description" content="Choose from specialized AI agents for coding, creative writing, data analysis, and more." />
        <meta name="twitter:image" content="/og-image.png" />
        
        <!-- Canonical -->
        <link rel="canonical" href="/" />
    </Head>

    <!-- Background Grid -->
    <div class="landing-page fixed inset-0 bg-[#fafafa] dark:bg-[#0a0a0a]">
        <div class="absolute inset-0 bg-grid-pattern"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-white/50 dark:to-[#0a0a0a]/80"></div>
    </div>

    <div class="landing-page relative min-h-screen">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 backdrop-blur-xl bg-white/60 dark:bg-[#0a0a0a]/60 border-b border-gray-200/50 dark:border-white/5">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2" aria-label="AI Chat Home">
                    <img src="/logo-ti.svg" alt="AI Chat Logo" class="h-6 dark:invert" />
                </a>
                <nav class="flex items-center gap-3" aria-label="Main navigation">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 transition-all"
                    >
                        Dashboard
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 transition-all"
                        >
                            Get Started
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="pt-32 pb-24 px-6" aria-labelledby="hero-heading">
            <div class="max-w-6xl mx-auto">
                <div class="text-center max-w-3xl mx-auto">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-400 text-xs font-medium mb-8 tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        AI-Powered Conversations
                    </div>
                    <h1 id="hero-heading" class="text-4xl sm:text-5xl md:text-6xl font-semibold tracking-tight text-gray-900 dark:text-white leading-[1.1]">
                        Your AI, Your Way.<br />
                        <span class="bg-gradient-to-r from-gray-900 via-gray-600 to-gray-400 dark:from-white dark:via-gray-300 dark:to-gray-500 bg-clip-text text-transparent">
                            Select Your Agent.
                        </span>
                    </h1>
                    <p class="mt-6 text-lg text-gray-500 dark:text-gray-400 max-w-xl mx-auto leading-relaxed">
                        Choose from specialized AI agents designed for different tasks. Get personalized assistance that understands your unique needs.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <Link
                            :href="$page.props.auth.user ? dashboard() : register()"
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 dark:bg-white px-6 py-3 text-sm font-medium text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 transition-all shadow-lg shadow-gray-900/10 dark:shadow-white/10"
                        >
                            Start Chatting
                            <ArrowRight class="w-4 h-4" />
                        </Link>
                        <Link
                            :href="login()"
                            v-if="!$page.props.auth.user"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-white/10 px-6 py-3 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-all"
                        >
                            Learn More
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Agent Selection Preview -->
        <section class="py-20 px-6" aria-labelledby="agents-heading">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h2 id="agents-heading" class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-white mb-3">
                        Meet Your Agents
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        Specialized AI personalities for every task
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="agent in agents"
                        :key="agent.name"
                        class="group relative bg-neutral-100 dark:bg-neutral-900 rounded-2xl p-5 border border-neutral-200 dark:border-neutral-700 hover:border-neutral-300 dark:hover:border-neutral-600 transition-all duration-300 hover:shadow-lg hover:shadow-neutral-300/50 dark:hover:shadow-black/20"
                    >
                        <div :class="`w-12 h-12 rounded-xl ${agent.bg} flex items-center justify-center mb-4`">
                            <component :is="agent.icon" :class="`w-6 h-6 ${agent.iconColor}`" />
                        </div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">{{ agent.name }}</h3>
                        <p class="text-gray-500 dark:text-gray-500 text-sm leading-relaxed">{{ agent.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20 px-6" aria-labelledby="features-heading">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h2 id="features-heading" class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-white mb-3">
                        How It Works
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        Simple, powerful, and intuitive
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        class="text-center p-6"
                    >
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-5">
                            <component :is="feature.icon" class="w-7 h-7 text-gray-600 dark:text-gray-300" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ feature.title }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 px-6" aria-labelledby="cta-heading">
            <div class="max-w-3xl mx-auto">
                <div class="relative rounded-2xl bg-neutral-900 p-10 sm:p-14 text-center overflow-hidden border border-neutral-800">
                    <div class="absolute inset-0 bg-gradient-to-t from-indigo-600/20 via-transparent to-transparent"></div>
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-3/4 h-px bg-gradient-to-r from-transparent via-indigo-500/50 to-transparent"></div>
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-1/2 h-32 bg-indigo-500/20 blur-3xl"></div>
                    <div class="absolute inset-0 bg-grid-pattern-dark"></div>
                    <div class="relative">
                        <h2 id="cta-heading" class="text-2xl sm:text-3xl font-semibold text-white mb-3">
                            Ready to get started?
                        </h2>
                        <p class="text-gray-400 mb-8 max-w-md mx-auto">
                            Join now and experience the future of AI conversation.
                        </p>
                        <Link
                            :href="$page.props.auth.user ? dashboard() : register()"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-medium text-gray-900 hover:bg-gray-100 transition-all"
                        >
                            Get Started Free
                            <ArrowRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-10 px-6 border-t border-gray-200/50 dark:border-white/5">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <img src="/logo-ti.svg" alt="Logo" class="h-5 dark:invert opacity-60" />
                </div>
                <p class="text-sm text-gray-400">
                    &copy; {{ new Date().getFullYear() }} All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.landing-page {
    font-family: 'IBM Plex Mono', ui-monospace, monospace;
}
.bg-grid-pattern {
    background-image: radial-gradient(circle, rgb(0 0 0 / 0.15) 1px, transparent 1px);
    background-size: 32px 32px;
}
.dark .bg-grid-pattern {
    background-image: radial-gradient(circle, rgb(255 255 255 / 0.15) 1px, transparent 1px);
}
.bg-grid-pattern-dark {
    background-image: radial-gradient(circle, rgb(255 255 255 / 0.2) 1px, transparent 1px);
    background-size: 32px 32px;
}
</style>
