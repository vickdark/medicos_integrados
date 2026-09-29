<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CircleCheck, CircleAlert, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

type Flash = {
    success?: string | null;
    error?: string | null;
};

const page = usePage<{ flash: Flash }>();
const visible = ref(false);

watch(
    () => page.props.flash,
    (flash) => {
        visible.value = Boolean(flash?.success || flash?.error);
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div
        v-if="visible && (page.props.flash.success || page.props.flash.error)"
        role="status"
        class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm"
        :class="
            page.props.flash.error
                ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
        "
    >
        <CircleAlert
            v-if="page.props.flash.error"
            class="mt-0.5 size-4 shrink-0"
        />
        <CircleCheck v-else class="mt-0.5 size-4 shrink-0" />
        <p class="flex-1">
            {{ page.props.flash.error ?? page.props.flash.success }}
        </p>
        <button
            type="button"
            class="opacity-70 hover:opacity-100"
            aria-label="Cerrar"
            @click="visible = false"
        >
            <X class="size-4" />
        </button>
    </div>
</template>
