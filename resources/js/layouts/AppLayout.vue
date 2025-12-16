<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    currentConversationId?: string;
    selectedModel?: string;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    currentConversationId: undefined,
    selectedModel: undefined,
});
</script>

<template>
    <AppLayout
        :breadcrumbs="breadcrumbs"
        :current-conversation-id="currentConversationId"
        :selected-model="selectedModel"
    >
        <slot />
    </AppLayout>
</template>
