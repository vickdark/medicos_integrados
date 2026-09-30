<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    label: string;
    value: string | number;
    hint?: string;
    /** Percentage change against the previous period; null hides it. */
    delta?: number | null;
    href?: string;
    /** Highlights a figure that needs attention (pending work). */
    attention?: boolean;
}>();

const deltaLabel = computed(() =>
    props.delta === null || props.delta === undefined
        ? null
        : `${props.delta > 0 ? '+' : ''}${props.delta}% vs mes anterior`,
);
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        class="flex flex-col gap-1 rounded-xl border bg-card p-4 text-card-foreground"
        :class="href ? 'transition-colors hover:bg-accent/50' : ''"
    >
        <p class="text-sm text-muted-foreground">{{ label }}</p>
        <p
            class="text-3xl font-semibold tracking-tight"
            :class="attention ? 'text-amber-600 dark:text-amber-400' : ''"
        >
            {{ value }}
        </p>
        <p
            v-if="deltaLabel"
            class="inline-flex items-center gap-1 text-xs font-medium"
            :class="
                (delta ?? 0) >= 0
                    ? 'text-emerald-700 dark:text-emerald-400'
                    : 'text-red-700 dark:text-red-400'
            "
        >
            <ArrowUpRight v-if="(delta ?? 0) >= 0" class="size-3.5" />
            <ArrowDownRight v-else class="size-3.5" />
            {{ deltaLabel }}
        </p>
        <p v-else-if="hint" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
    </component>
</template>
