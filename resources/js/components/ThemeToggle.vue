<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, toggleAppearance } = useAppearance();

const label = computed(() =>
    appearance.value === 'dark'
        ? 'Cambiar a modo claro'
        : 'Cambiar a modo oscuro',
);
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        :aria-label="label"
        :title="label"
        data-test="theme-toggle"
        @click="toggleAppearance"
    >
        <Moon v-if="appearance === 'light'" class="size-5" />
        <Sun v-else class="size-5" />
    </Button>
</template>
