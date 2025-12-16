<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import ConversationSidebar from '@/layouts/app/ConversationSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
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
    <AppShell variant="sidebar">
        <ConversationSidebar
            :current-conversation-id="currentConversationId"
            :selected-model="selectedModel"
        />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
