<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { Avatar, AvatarFallback } from '@/components/ui';

const page = usePage();

const props = defineProps({
    selectedModel: {
        type: String,
        default: undefined,
    },
});

// get user from shared data
const user = computed(() => page.props.auth?.user);
const userInitials = computed(() => (user.value?.name ?? '').trim().slice(0, 2).toUpperCase());

// user dropdown
const showUserMenu = ref(false);
</script>

<template>
    <header class="flex items-center justify-between border-b px-6 py-3">
        <span v-if="selectedModel" class="text-sm text-muted-foreground">{{ selectedModel }}</span>
        <div v-else />

        <!-- user dropdown menu -->
        <div v-if="user" class="relative">
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
</template>
