<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, EyeOff, Key, Trash2 } from 'lucide-vue-next';

interface Props {
    hasApiKey: boolean;
    maskedKey: string | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Key settings',
        href: '/settings/api-key',
    },
];

const showKey = ref(false);

const form = useForm({
    api_key: '',
});

const deleteForm = useForm({});

const submit = () => {
    form.patch('/settings/api-key', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const removeKey = () => {
    if (confirm('Are you sure you want to remove your API key?')) {
        deleteForm.delete('/settings/api-key', {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="API Key settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="API Key"
                    description="Manage your API key for AI services"
                />

                <!-- Current API Key Status -->
                <div v-if="hasApiKey" class="rounded-lg border border-border p-4 bg-muted/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                <Key class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">API Key configured</p>
                                <p class="text-sm text-muted-foreground font-mono">{{ maskedKey }}</p>
                            </div>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:text-destructive hover:bg-destructive/10"
                            @click="removeKey"
                            :disabled="deleteForm.processing"
                        >
                            <Trash2 class="w-4 h-4 mr-1" />
                            Remove
                        </Button>
                    </div>
                </div>

                <!-- Update/Add API Key Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="api_key">{{ hasApiKey ? 'Update API Key' : 'API Key' }}</Label>
                        <div class="relative">
                            <Input
                                id="api_key"
                                :type="showKey ? 'text' : 'password'"
                                class="mt-1 block w-full pr-10 font-mono"
                                v-model="form.api_key"
                                :placeholder="hasApiKey ? 'Enter new API key to update' : 'Enter your API key'"
                                autocomplete="off"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                @click="showKey = !showKey"
                            >
                                <EyeOff v-if="showKey" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.api_key" />
                        <p class="text-sm text-muted-foreground">
                            Your API key is encrypted and stored securely. It will be used for AI model requests.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing || !form.api_key">
                            {{ hasApiKey ? 'Update API Key' : 'Save API Key' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-if="form.recentlySuccessful"
                                class="text-sm text-emerald-600 dark:text-emerald-400"
                            >
                                Saved successfully.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
