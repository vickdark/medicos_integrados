<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import FlashMessage from '@/components/FlashMessage.vue';
import IdleLogoutDialog from '@/components/IdleLogoutDialog.vue';
import PushPrompt from '@/components/PushPrompt.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="px-4 pt-4 empty:hidden">
                <FlashMessage />
            </div>
            <div class="px-4 pt-4 empty:hidden">
                <PushPrompt />
            </div>
            <slot />
        </AppContent>
        <IdleLogoutDialog />
    </AppShell>
</template>
