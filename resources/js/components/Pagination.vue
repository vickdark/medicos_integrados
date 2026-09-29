<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/models';

defineProps<{
    paginator: Paginated<unknown>;
}>();
</script>

<template>
    <div
        v-if="paginator.last_page > 1"
        class="flex items-center justify-between gap-4 text-sm text-muted-foreground"
    >
        <span>
            {{ paginator.from }}–{{ paginator.to }} de {{ paginator.total }}
        </span>
        <div class="flex gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!paginator.prev_page_url"
                as-child
            >
                <Link
                    v-if="paginator.prev_page_url"
                    :href="paginator.prev_page_url"
                    preserve-scroll
                    >Anterior</Link
                >
                <span v-else>Anterior</span>
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="!paginator.next_page_url"
                as-child
            >
                <Link
                    v-if="paginator.next_page_url"
                    :href="paginator.next_page_url"
                    preserve-scroll
                    >Siguiente</Link
                >
                <span v-else>Siguiente</span>
            </Button>
        </div>
    </div>
</template>
