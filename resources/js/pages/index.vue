<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 p-6 dark:bg-gray-900">
        <div class="flex items-center justify-center text-foreground p-8">
            <img src="/logo-ti.svg" alt="Logo" class="h-5" />
        </div>

        <nav class="flex items-center gap-4">
            <Link
                v-if="$page.props.auth.user"
                :href="dashboard()"
                class="rounded-md bg-gray-900 px-6 py-2.5 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
            >
                Dashboard
            </Link>
            <template v-else>
                <Link
                    :href="login()"
                    class="rounded-md border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    Log in
                </Link>
                <Link
                    v-if="canRegister"
                    :href="register()"
                    class="rounded-md bg-gray-900 px-6 py-2.5 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                >
                    Register
                </Link>
            </template>
        </nav>
    </div>
</template>
