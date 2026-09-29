<script setup lang="ts">
import { HeartPulse } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-teal-600 text-white"
    >
        <HeartPulse class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold"
            >Médicos Integrados</span
        >
    </div>
</template>
