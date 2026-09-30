<script setup lang="ts">
import { computed } from 'vue';
import type { Option } from '@/types/models';

const props = defineProps<{
    status: Option;
}>();

const tones: Record<string, string> = {
    requested:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    confirmed: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    completed:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    cancelled:
        'bg-neutral-200 text-neutral-700 dark:bg-neutral-500/20 dark:text-neutral-300',
    voided: 'bg-neutral-200 text-neutral-700 dark:bg-neutral-500/20 dark:text-neutral-300',
    waiting:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    called: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    done: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
};

const toneClass = computed(() => tones[props.status.value] ?? tones.cancelled);
</script>

<template>
    <span
        class="inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
        :class="toneClass"
    >
        {{ status.label }}
    </span>
</template>
